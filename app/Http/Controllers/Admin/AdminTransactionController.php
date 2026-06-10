<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminTransactionController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request)
    {
        try {
            $transactions = Transaction::with('user', 'cycle.tontine')
                ->when($request->search, function ($q) use ($request) {
                    $q->where(function ($sub) use ($request) {
                        $sub->where('id', 'like', '%'.$request->search.'%')
                            ->orWhereHas('user', fn ($u) => $u
                                ->where('name', 'like', '%'.$request->search.'%')
                                ->orWhere('phone_number', 'like', '%'.$request->search.'%')
                            );
                    });
                })
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->method, fn ($q) => $q->where('method', $request->method))
                ->when($request->suspicious, fn ($q) => $q->where('amount', '>', config('tontine.transaction.daily_limit')))
                ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
                ->when($request->date_to,   fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
                ->latest()
                ->paginate(25);

            return view('admin.transactions', compact('transactions'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste transactions admin', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement des transactions.']);
        }
    }

    public function forceConfirm(Transaction $transaction)
    {
        try {
            if ($transaction->status === 'success') {
                return back()->withErrors(['error' => 'Transaction déjà confirmée.']);
            }
            if (in_array($transaction->status, ['reversed', 'failed'])) {
                return back()->withErrors(['error' => 'Impossible de confirmer une transaction annulée ou échouée.']);
            }

            $this->paymentService->confirmPayment($transaction);

            return back()->with('success', "Transaction #{$transaction->id} confirmée manuellement.");
        } catch (\Throwable $e) {
            Log::error('Erreur confirmation forcée transaction', ['tx' => $transaction->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de la confirmation.']);
        }
    }

    public function export()
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="transactions-tontinesn-'.now()->format('Y-m-d').'.csv"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Utilisateur', 'Email', 'Tontine', 'Cycle', 'Montant (FCFA)', 'Méthode', 'Statut', 'Date'], ';');

                Transaction::with('user', 'cycle.tontine')->latest()->chunk(500, function ($txs) use ($file) {
                    foreach ($txs as $tx) {
                        fputcsv($file, [
                            $tx->id,
                            $tx->user->name ?? '—',
                            $tx->user->email ?? '—',
                            $tx->cycle->tontine->name ?? '—',
                            $tx->cycle->cycle_number ?? '—',
                            $tx->amount,
                            $tx->method_label,
                            $tx->status_label,
                            $tx->paid_at?->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

                fclose($file);
            }, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Erreur export CSV transactions', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => "Erreur lors de l'export."]);
        }
    }
}
