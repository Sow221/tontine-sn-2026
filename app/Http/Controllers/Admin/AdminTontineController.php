<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminTontineController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        try {
            $tontines = Tontine::with('creator')
                ->withCount(['members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active')])
                ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                    $safe = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                    $q2->where('name', 'like', "%{$safe}%")
                        ->orWhere('code', 'like', "%{$safe}%");
                }))
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->latest()
                ->paginate(20);

            return view('admin.tontines', compact('tontines'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste tontines admin', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement des tontines.']);
        }
    }

    public function show(Tontine $tontine)
    {
        try {
            $tontine->load([
                'creator',
                'cycles' => fn ($q) => $q->orderBy('cycle_number'),
                'cycles.beneficiary',
            ]);
            $tontine->loadCount([
                'members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active'),
                'members as pending_members_count' => fn ($q) => $q->where('tontine_members.status', 'pending'),
            ]);

            $members = $tontine->members()
                ->withPivot('status', 'position', 'joined_at')
                ->get();

            $totalCollected = $tontine->cycles->sum('total_collected');
            $cyclesPaid = $tontine->cycles->where('status', 'paid')->count();

            $currentCycle = $tontine->cycles->first(fn ($c) => in_array($c->status, ['active', 'partial', 'overdue']));
            $cycleTransactions = $currentCycle
                ? Transaction::where('cycle_id', $currentCycle->id)
                    ->whereIn('status', ['success', 'pending'])
                    ->with('user')
                    ->get()
                    ->keyBy('user_id')
                : collect();

            return view('admin.tontine-detail', compact('tontine', 'members', 'totalCollected', 'cyclesPaid', 'currentCycle', 'cycleTransactions'));
        } catch (\Throwable $e) {
            Log::error('Erreur détail tontine admin', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement.']);
        }
    }

    public function forceCloseCycle(Tontine $tontine, Cycle $cycle)
    {
        try {
            if (! in_array($cycle->status, ['active', 'partial', 'overdue'])) {
                return back()->withErrors(['error' => 'Ce cycle ne peut pas être forcé (statut incompatible).']);
            }

            $cycle->update(['status' => 'paid']);

            $this->notifications->send(
                $tontine->creator,
                'general',
                "Le cycle {$cycle->cycle_number} de la tontine « {$tontine->name} » a été clôturé manuellement par un administrateur."
            );

            Cache::forget('admin.stats');

            return back()->with('success', "Cycle {$cycle->cycle_number} clôturé.");
        } catch (\Throwable $e) {
            Log::error('Erreur clôture forcée cycle', ['cycle' => $cycle->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de la clôture du cycle.']);
        }
    }

    public function suspend(Tontine $tontine)
    {
        try {
            if ($tontine->status === 'suspended') {
                return back()->withErrors(['error' => 'Cette tontine est déjà suspendue.']);
            }

            $tontine->update(['status' => 'suspended']);
            $this->notifications->send($tontine->creator, 'general', "Votre tontine « {$tontine->name} » a été suspendue par un administrateur.");

            Cache::forget('admin.recent_tontines');
            Cache::forget('admin.stats');

            return back()->with('success', "Tontine « {$tontine->name} » suspendue.");
        } catch (\Throwable $e) {
            Log::error('Erreur suspension tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de la suspension.']);
        }
    }

    public function reactivate(Tontine $tontine)
    {
        try {
            if ($tontine->status !== 'suspended') {
                return back()->withErrors(['error' => 'Seule une tontine suspendue peut être réactivée.']);
            }

            $tontine->update(['status' => 'active']);
            $this->notifications->send($tontine->creator, 'general', "Votre tontine « {$tontine->name} » a été réactivée par un administrateur.");

            Cache::forget('admin.recent_tontines');
            Cache::forget('admin.stats');

            return back()->with('success', "Tontine « {$tontine->name} » réactivée.");
        } catch (\Throwable $e) {
            Log::error('Erreur réactivation tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de la réactivation.']);
        }
    }
}
