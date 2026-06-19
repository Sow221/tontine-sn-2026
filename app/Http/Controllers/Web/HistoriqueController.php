<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HistoriqueController extends Controller
{
    public function export(Request $request)
    {
        try {
            $user = Auth::user();
            $query = $user->transactions()->with('cycle.tontine')->latest();

            if ($request->filled('tontine_id')) {
                $query->whereHas('cycle', fn ($q) => $q->where('tontine_id', $request->tontine_id));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('periode') && preg_match('/^\d{4}-\d{2}$/', $request->periode)) {
                [$year, $month] = explode('-', $request->periode);
                $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
            }

            $transactions = $query->get();

            $filename = 'historique-tontinesn-'.now()->format('Y-m-d').'.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($transactions) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['Date', 'Tontine', 'Cycle', 'Montant (FCFA)', 'Méthode', 'Statut'], ';');

                foreach ($transactions as $tx) {
                    fputcsv($file, [
                        $tx->paid_at?->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i'),
                        $tx->cycle->tontine->name ?? '—',
                        'Cycle '.($tx->cycle->cycle_number ?? '—'),
                        $tx->amount,
                        $tx->method_label,
                        $tx->status_label,
                    ], ';');
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Erreur export historique', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors de l\'export.']);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tontines = $user->memberships()->get(['tontines.id', 'tontines.name']);

            $query = $user->transactions()->with('cycle.tontine')->latest();

            if ($request->filled('tontine_id')) {
                $query->whereHas('cycle', fn ($q) => $q->where('tontine_id', $request->tontine_id));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('operateur')) {
                $query->where('method', $request->operateur);
            }
            if ($request->filled('type_flux')) {
                if ($request->type_flux === 'cotisation') {
                    $query->where('type', 'cotisation');
                } else {
                    $query->whereIn('type', ['redistribution', 'payout', 'p2p_transfer']);
                }
            }
            if ($request->filled('date_debut')) {
                $query->whereDate('created_at', '>=', $request->date_debut);
            }
            if ($request->filled('date_fin')) {
                $query->whereDate('created_at', '<=', $request->date_fin);
            }
            if ($request->filled('search')) {
                $s = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(fn ($q) => $q
                    ->where('external_reference', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$s}%")
                        ->orWhere('phone_number', 'like', "%{$s}%")
                    )
                );
            }
            if ($request->filled('periode') && preg_match('/^\d{4}-\d{2}$/', $request->periode)) {
                [$year, $month] = explode('-', $request->periode);
                $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
            }

            $totalSuccess = (clone $query)->where('status', 'success')->sum('amount');
            $transactions = $query->paginate(20)->withQueryString();

            $periodes = collect();
            for ($i = 0; $i < 12; $i++) {
                $date = now()->subMonths($i);
                $periodes->push(['value' => $date->format('Y-m'), 'label' => $date->isoFormat('MMMM YYYY')]);
            }

            // Cycles où l'utilisateur était bénéficiaire — pots reçus
            $decaissements = Cycle::where('beneficiary_id', $user->id)
                ->where('status', 'paid')
                ->with('tontine')
                ->orderByDesc('drawn_at')
                ->get();

            return view('historique.index', compact('transactions', 'tontines', 'totalSuccess', 'periodes', 'decaissements'));
        } catch (\Throwable $e) {
            Log::error('Erreur historique', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du chargement de l\'historique.']);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $user = Auth::user();
            $query = $user->transactions()->with('cycle.tontine')->latest();

            if ($request->filled('tontine_id')) {
                $query->whereHas('cycle', fn ($q) => $q->where('tontine_id', $request->tontine_id));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('periode') && preg_match('/^\d{4}-\d{2}$/', $request->periode)) {
                [$year, $month] = explode('-', $request->periode);
                $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
            }

            $transactions = $query->get();

            $options = new Options;
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml(view('historique.pdf', compact('transactions', 'user'))->render());
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename = 'historique-complet-tontinesn-'.now()->format('Y-m-d').'.pdf';

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur export PDF historique', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors de l\'export PDF.']);
        }
    }
}
