<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
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
                    $q2->where('name', 'like', "%{$request->search}%")
                        ->orWhere('code', 'like', "%{$request->search}%");
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

            return view('admin.tontine-detail', compact('tontine', 'members', 'totalCollected', 'cyclesPaid'));
        } catch (\Throwable $e) {
            Log::error('Erreur détail tontine admin', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement.']);
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
