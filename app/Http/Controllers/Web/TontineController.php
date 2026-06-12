<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTontineRequest;
use App\Http\Requests\UpdateTontineRequest;
use App\Jobs\ProcessCycle;
use App\Models\SavingsWithdrawal;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CycleService;
use App\Services\DrawService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\TontineService;
use App\Services\WebhookOutboundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TontineController extends Controller
{
    public function __construct(
        private TontineService $service,
        private CycleService $cycleService,
        private PaymentService $paymentService,
        private NotificationService $notifier,
        private DrawService $drawService,
        private WebhookOutboundService $webhookOutbound,
    ) {}

    public function explore(Request $request)
    {
        try {
            $query = Tontine::publiclyVisible()
                ->withCount(['members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active')])
                ->with('creator');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($type = $request->input('type')) {
                $query->where('type', $type);
            }

            if ($freq = $request->input('frequency')) {
                $query->where('frequency', $freq);
            }

            if ($max = $request->input('max_amount')) {
                $query->where('amount', '<=', $max);
            }

            $sort = $request->input('sort', 'latest');
            match ($sort) {
                'amount_asc' => $query->orderBy('amount', 'asc'),
                'amount_desc' => $query->orderBy('amount', 'desc'),
                'spots' => $query->orderByRaw('(tontines.max_members - (SELECT COUNT(*) FROM tontine_members WHERE tontine_members.tontine_id = tontines.id AND tontine_members.status = ?)) desc', ['active']),
                default => $query->latest(),
            };

            $tontines = $query->paginate(12)->withQueryString();

            $myTontineIds = Auth::user()
                ->memberships()
                ->pluck('tontines.id')
                ->toArray();

            return view('tontines.explore', compact('tontines', 'myTontineIds'));
        } catch (\Throwable $e) {
            Log::error('Erreur explorer tontines', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du chargement du catalogue.']);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = Auth::user()->memberships()
                ->withPivot('status', 'position')
                ->with('creator', 'cycles')
                ->withCount(['members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active')]);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('tontines.name', 'like', "%{$search}%")
                        ->orWhere('tontines.code', 'like', "%{$search}%");
                });
            }

            if ($status = $request->input('status')) {
                $query->where('tontines.status', $status);
            }

            $tontines = $query->paginate(10)->withQueryString();

            return view('tontines.index', compact('tontines'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste tontines', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du chargement des tontines.']);
        }
    }

    public function create()
    {
        return view('tontines.create');
    }

    public function store(StoreTontineRequest $request)
    {
        try {
            $tontine = Tontine::create([
                ...$request->validated(),
                'created_by' => Auth::id(),
            ]);

            $tontine->members()->attach(Auth::id(), [
                'status' => 'active',
                'position' => 1,
                'joined_at' => now(),
            ]);

            return redirect()->route('tontines.show', $tontine)
                ->with('success', 'Tontine créée avec succès !');
        } catch (\Throwable $e) {
            Log::error('Erreur création tontine', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            $msg = match (true) {
                str_contains($e->getMessage(), 'visibility') => 'La colonne visibility est manquante. Exécutez php artisan migrate.',
                str_contains($e->getMessage(), 'SQLSTATE[23000]') => 'Cette tontine existe déjà avec le même code.',
                default => 'Erreur lors de la création de la tontine. Vérifiez les champs et réessayez.',
            };
            return back()->withErrors(['error' => $msg])->withInput();
        }
    }

    public function show(Tontine $tontine)
    {
        $this->authorize('view', $tontine);

        try {
            // Chargement unique et complet — évite le double load et les N+1
            $tontine->load([
                'members',
                'cycles' => fn ($q) => $q->select('id', 'tontine_id', 'cycle_number', 'due_date', 'status', 'total_collected', 'beneficiary_id', 'drawn_at', 'bid_amount')->orderBy('cycle_number'),
                'currentCycle.transactions',
                'currentCycle.auctionBids',
                'currentCycle.beneficiary',
            ]);
            $currentCycle = $tontine->currentCycle;
            $user = Auth::user();
            $userId = $user->id;

            $hasPaid = $currentCycle
                && Transaction::success()->forCycle($currentCycle->id)->forUser($userId)->exists();

            $paymentPending = $currentCycle
                && Transaction::pending()->forCycle($currentCycle->id)->forUser($userId)->exists();

            $paidMemberIds = $currentCycle
                ? $currentCycle->successfulTransactions()->pluck('user_id')
                : collect();

            $drawBlockReason = $currentCycle ? $this->drawService->canDraw($currentCycle) : null;
            $canDraw = $currentCycle
                && ! $currentCycle->beneficiary_id
                && $drawBlockReason === null;

            $canVeto = $currentCycle && $this->drawService->canVeto($currentCycle, $userId);
            $hasVetoed = $currentCycle && $this->drawService->hasVoted($currentCycle, $userId);
            $vetoCount = $currentCycle ? $this->drawService->vetoCount($currentCycle) : 0;
            $vetoRequired = $currentCycle && $tontine->veto_threshold
                ? (int) ceil($tontine->activeMembers()->count() * $tontine->veto_threshold / 100)
                : 0;

            $myMember = $tontine->members->firstWhere('id', $userId);
            $myMemberStatus = $myMember?->pivot->status;
            $myPosition = $myMember?->pivot->position;

            $totalCollecte = $tontine->cycles->sum('total_collected');
            $cyclesPaids = $tontine->cycles->where('status', 'paid')->count();
            $myPastWin = $tontine->cycles->firstWhere('beneficiary_id', $userId);
            $turnEstimate = $myMemberStatus === 'active'
                ? $tontine->turnEstimateFor($userId)
                : null;

            $inviteUrl = route('tontines.join.form', ['code' => $tontine->code]);
            $acceptsNewMembers = $tontine->acceptsNewMembers();
            $bidDeadlinePassed = $currentCycle && $currentCycle->due_date->isPast();

            $pastCycles = $tontine->cycles
                ->where('status', 'paid')
                ->sortBy('cycle_number')
                ->values();

            $lastSuccessTransaction = $currentCycle
                ? Transaction::success()->forCycle($currentCycle->id)->forUser($userId)->latest('paid_at')->first()
                : null;

            $mySaved = null;
            $myWithdrawal = null;
            $withdrawals = collect();

            if ($tontine->type === 'forced_saving') {
                $mySaved = Transaction::success()->forTontine($tontine->id)->forUser($userId)
                    ->excludeRedistribution()
                    ->sum('amount');

                $myWithdrawal = SavingsWithdrawal::where('tontine_id', $tontine->id)
                    ->where('user_id', $userId)
                    ->first();

                if ($userId === $tontine->created_by) {
                    $withdrawals = SavingsWithdrawal::where('tontine_id', $tontine->id)
                        ->with('user')
                        ->get();
                }
            }

            return view('tontines.show', compact(
                'tontine', 'currentCycle', 'hasPaid', 'paymentPending', 'paidMemberIds', 'canDraw', 'drawBlockReason',
                'canVeto', 'hasVetoed', 'vetoCount', 'vetoRequired',
                'myMemberStatus', 'totalCollecte', 'cyclesPaids', 'myPosition', 'myPastWin', 'turnEstimate',
                'inviteUrl', 'acceptsNewMembers', 'mySaved', 'myWithdrawal', 'withdrawals', 'pastCycles',
                'bidDeadlinePassed', 'lastSuccessTransaction'
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur affichage tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du chargement de la tontine.']);
        }
    }

    public function edit(Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        return view('tontines.edit', compact('tontine'));
    }

    public function update(UpdateTontineRequest $request, Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        try {
            if ($tontine->status === 'active' && $request->has('amount') && $request->amount != $tontine->amount) {
                return back()->withErrors(['amount' => 'Le montant ne peut pas être modifié sur une tontine active.']);
            }

            $tontine->update($request->validated());

            return redirect()->route('tontines.show', $tontine)
                ->with('success', 'Tontine mise à jour.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors de la mise à jour de la tontine.']);
        }
    }

    public function activate(Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        try {
            if ($tontine->activeMembers()->count() < 2) {
                return back()->withErrors(['activate' => 'La tontine doit avoir au moins 2 membres actifs pour être activée.']);
            }

            if ($tontine->cycles()->exists()) {
                return back()->withErrors(['activate' => 'Cette tontine a déjà été activée.']);
            }

            $tontine->update(['status' => 'active']);

            if (config('queue.default') === 'sync') {
                $this->cycleService->createCycles($tontine);

                return back()->with('success', 'Tontine activée et '.$tontine->cycles()->count().' cycles générés.');
            }

            ProcessCycle::dispatch($tontine)->afterResponse();

            return back()->with('success', 'Tontine activée. Les cycles sont en cours de génération (quelques secondes).');
        } catch (\Throwable $e) {
            Log::error('Erreur activation tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            $actMsg = str_contains($e->getMessage(), 'cycles') ? 'Erreur lors de la génération des cycles.' : 'Erreur lors de l\'activation. Vérifiez que la tontine a au moins 2 membres actifs.';
            return back()->withErrors(['activate' => $actMsg]);
        }
    }

    public function showJoinForm(Request $request)
    {
        $code = strtoupper($request->query('code', ''));

        $preview = null;
        if ($code) {
            $preview = Tontine::where('code', $code)
                ->withCount(['members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active')])
                ->first();
        }

        return view('tontines.join', compact('code', 'preview'));
    }

    public function showInvite(string $code)
    {
        try {
            $tontine = Tontine::where('code', strtoupper($code))->first();
            if (! $tontine) {
                abort(404);
            }

            // Stocker le code en session pour redirection post-auth
            session(['invite_code' => strtoupper($code)]);

            $inviteUrl = route('tontines.join.form', ['code' => $tontine->code]);
            $ogImage = route('tontines.og', ['code' => $tontine->code]);
            $excerpt = $tontine->description ?: "Rejoignez cette tontine avec le code {$tontine->code}.";
            $title = $tontine->name;

            return view('tontines.invite', compact('tontine', 'inviteUrl', 'ogImage', 'excerpt', 'title'));
        } catch (\Throwable $e) {
            Log::error('Erreur affichage invitation', ['code' => $code, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            abort(404);
        }
    }

    public function ogInviteImage(string $code)
    {
        try {
            $tontine = Tontine::where('code', strtoupper($code))->first();
            if (! $tontine) {
                abort(404);
            }

            $amount = number_format($tontine->amount, 0, ',', ' ');
            $members = $tontine->activeMembers()->count();
            $status = match ($tontine->status) {
                'active' => 'Active', 'completed' => 'Terminée', default => 'En attente'
            };

            $safeName   = htmlspecialchars($tontine->name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $safeCode   = htmlspecialchars($tontine->code, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $safeAmount = htmlspecialchars($amount, ENT_XML1, 'UTF-8');
            $safeStatus = htmlspecialchars($status, ENT_XML1, 'UTF-8');

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#f0fdf4"/>
      <stop offset="100%" stop-color="#dcfce7"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)" rx="0"/>
  <rect x="0" y="530" width="1200" height="100" fill="#009639"/>
  <text x="60" y="100" font-family="system-ui, sans-serif" font-size="48" font-weight="800" fill="#111827">{$safeName}</text>
  <text x="60" y="160" font-family="system-ui, sans-serif" font-size="28" fill="#6b7280">Code&#160;: {$safeCode}</text>
  <rect x="60" y="200" width="1080" height="2" fill="#e5e7eb"/>
  <text x="60" y="260" font-family="system-ui, sans-serif" font-size="24" fill="#374151">Montant&#160;: {$safeAmount} FCFA par cycle</text>
  <text x="60" y="310" font-family="system-ui, sans-serif" font-size="24" fill="#374151">Membres&#160;: {$members}</text>
  <text x="60" y="360" font-family="system-ui, sans-serif" font-size="24" fill="#374151">Statut&#160;: {$safeStatus}</text>
  <text x="60" y="590" font-family="system-ui, sans-serif" font-size="32" font-weight="700" fill="#ffffff">TontineSN</text>
  <text x="1140" y="590" font-family="system-ui, sans-serif" font-size="20" fill="rgba(255,255,255,0.8)" text-anchor="end">Rejoindre avec le code</text>
</svg>
SVG;

            return response($svg, 200, [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'image/svg+xml',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur génération OG image', ['code' => $code, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            abort(404);
        }
    }

    public function join(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        try {
            $tontine = Tontine::where('code', strtoupper($request->code))->first();
            $result = $this->service->joinTontine($tontine, Auth::id());

            if (! $result['ok']) {
                if (! empty($result['already']) && $tontine) {
                    return redirect()->route('tontines.show', $tontine)->with('success', $result['message']);
                }

                return back()->withErrors(['code' => $result['message']]);
            }

            // Webhook sortant : membre ayant rejoint
            if ($tontine) {
                $this->webhookOutbound->dispatch('member.joined', [
                    'tontine_id' => $tontine->id,
                    'user_id' => Auth::id(),
                    'status' => 'pending',
                ]);
            }

            return redirect()->route('tontines.show', $tontine)->with('success', $result['message']);
        } catch (\Throwable $e) {
            \Log::error('Erreur adhésion tontine', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['code' => 'Erreur lors de l\'adhésion.']);
        }
    }

    public function confirmCashPayment(Tontine $tontine, Transaction $transaction)
    {
        $this->authorize('update', $tontine);

        abort_unless($transaction->method === 'cash' && $transaction->status === 'pending', 403);
        abort_unless($transaction->cycle?->tontine_id === $tontine->id, 403);

        try {
            $this->paymentService->confirmPayment($transaction);
            $transaction->load('user');
            $this->notifier->notifyPaymentConfirmed(
                $transaction->user,
                $transaction->amount,
                $tontine->name,
                $transaction->cycle?->cycle_number
            );

            return back()->with('success', 'Paiement espèces de '.($transaction->user->name ?? '—').' confirmé.');
        } catch (\Throwable $e) {
            Log::error('Erreur confirmation cash', ['tx' => $transaction->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de la confirmation.']);
        }
    }

    public function remindAll(Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        $currentCycle = $tontine->currentCycle;
        if (! $currentCycle) {
            return back()->withErrors(['error' => 'Aucun cycle actif.']);
        }

        try {
            $paidIds = Transaction::success()
                ->forCycle($currentCycle->id)
                ->pluck('user_id')
                ->toArray();

            $unpaid = $tontine->activeMembers()
                ->whereNotIn('users.id', $paidIds)
                ->get();

            foreach ($unpaid as $member) {
                $this->notifier->notifyPaymentReminder(
                    $member,
                    $tontine->name,
                    $tontine->amount,
                    max(0, now()->diffInDays($currentCycle->due_date, false))
                );
            }

            return back()->with('success', 'Rappel envoyé à ' . $unpaid->count() . ' membre(s).');
        } catch (\Throwable $e) {
            Log::error('Erreur relance groupee', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de l\'envoi des rappels.']);
        }
    }

    public function remindMember(Tontine $tontine, User $user)
    {
        $this->authorize('update', $tontine);

        $currentCycle = $tontine->currentCycle;
        if (! $currentCycle) {
            return back()->withErrors(['error' => 'Aucun cycle actif pour cette tontine.']);
        }

        $alreadyPaid = Transaction::success()
            ->forCycle($currentCycle->id)
            ->forUser($user->id)
            ->exists();

        if ($alreadyPaid) {
            return back()->withErrors(['error' => $user->name.' a déjà payé ce cycle.']);
        }

        try {
            $this->notifier->notifyPaymentReminder(
                $user,
                $tontine->name,
                $tontine->amount,
                max(0, now()->diffInDays($currentCycle->due_date, false))
            );

            return back()->with('success', 'Rappel envoyé à '.$user->name.'.');
        } catch (\Throwable $e) {
            Log::error('Erreur relance membre', ['tontine' => $tontine->id, 'user' => $user->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de l\'envoi du rappel.']);
        }
    }

    public function approveMember(Tontine $tontine, User $user)
    {
        $this->authorize('update', $tontine);

        try {
            $tontine->members()->updateExistingPivot($user->id, ['status' => 'active']);
            Cache::forget("tontine_member_{$tontine->id}_{$user->id}");
            $this->notifier->notifyMemberApproved($user, $tontine->name);

            return back()->with('success', $user->name.' a été approuvé.');
        } catch (\Throwable $e) {
            Log::error('Erreur approbation membre', ['tontine' => $tontine->id, 'user' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors de l\'approbation.']);
        }
    }

    public function rejectMember(Tontine $tontine, User $user)
    {
        $this->authorize('update', $tontine);

        try {
            $tontine->members()->updateExistingPivot($user->id, ['status' => 'excluded']);
            Cache::forget("tontine_member_{$tontine->id}_{$user->id}");

            return back()->with('success', 'Membre refusé.');
        } catch (\Throwable $e) {
            Log::error('Erreur refus membre', ['tontine' => $tontine->id, 'user' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du refus.']);
        }
    }

    public function setBeneficiary(Request $request, Tontine $tontine)
    {
        abort_unless($tontine->type === 'ceremonial', 403);
        $this->authorize('update', $tontine);

        $request->validate([
            'beneficiary_id' => ['required', 'exists:users,id'],
        ]);

        try {
            $member = $tontine->members()
                ->where('users.id', $request->beneficiary_id)
                ->wherePivot('status', 'active')
                ->exists();

            if (! $member) {
                return back()->withErrors(['beneficiary_id' => 'Le membre sélectionné n\'est pas un membre actif de cette tontine.']);
            }

            $cycle = $tontine->cycles()->first();
            if ($cycle && ! $cycle->beneficiary_id) {
                $cycle->update(['beneficiary_id' => $request->beneficiary_id]);
            }

            return back()->with('success', 'Bénéficiaire mis à jour avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur changement bénéficiaire', ['tontine' => $tontine->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du changement de bénéficiaire.']);
        }
    }

    public function confirmWithdrawal(SavingsWithdrawal $withdrawal)
    {
        $tontine = $withdrawal->tontine;
        $this->authorize('update', $tontine);

        try {
            $this->paymentService->confirmWithdrawal($withdrawal);

            return back()->with('success', 'Versement confirmé pour '.($withdrawal->user->name ?? 'membre').'.');
        } catch (\Throwable $e) {
            Log::error('Erreur confirmation retrait', ['withdrawal' => $withdrawal->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors de la confirmation du versement.']);
        }
    }

    public function transferOwnership(Request $request, Tontine $tontine)
    {
        $this->authorize('update', $tontine);

        $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
        ], [
            'new_owner_id.required' => 'Veuillez sélectionner un nouveau propriétaire.',
            'new_owner_id.exists' => 'Membre introuvable.',
        ]);

        $newOwnerId = (int) $request->new_owner_id;

        if ($newOwnerId === Auth::id()) {
            return back()->withErrors(['new_owner_id' => 'Vous êtes déjà le propriétaire.']);
        }

        $isMember = $tontine->members()
            ->where('users.id', $newOwnerId)
            ->wherePivot('status', 'active')
            ->exists();

        if (! $isMember) {
            return back()->withErrors(['new_owner_id' => 'Le membre sélectionné n\'est pas un membre actif de cette tontine.']);
        }

        try {
            $tontine->update(['created_by' => $newOwnerId]);
            $newOwner = User::find($newOwnerId);
            $this->notifier->send(
                $newOwner,
                'general',
                "Vous êtes maintenant propriétaire de la tontine \u00ab\ {$tontine->name}\ \u00bb. Bonne gestion !"
            );

            return back()->with('success', 'Propriété transférée à '.($newOwner?->name ?? 'ce membre').'.');
        } catch (\Throwable $e) {
            Log::error('Erreur transfert propriété', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du transfert.']);
        }
    }

    public function leave(Tontine $tontine)
    {
        $user = Auth::user();

        try {
            $memberStatus = $tontine->members()
                ->where('users.id', $user->id)
                ->first()?->pivot?->status;

            if (! $memberStatus) {
                return back()->withErrors(['leave' => 'Vous n\'êtes pas membre de cette tontine.']);
            }

            if ($tontine->status === 'active' && $memberStatus === 'active') {
                $hasTurn = $tontine->cycles()->where('beneficiary_id', $user->id)->exists();
                if ($hasTurn) {
                    return back()->withErrors(['leave' => 'Vous ne pouvez pas quitter une tontine active dans laquelle vous avez un tour assigné.']);
                }
            }

            if ($tontine->created_by === $user->id) {
                return back()->withErrors(['leave' => 'Le créateur ne peut pas quitter sa propre tontine. Transférez la propriété à un autre membre actif avant de quitter.']);
            }

            $tontine->members()->detach($user->id);
            Cache::forget("tontine_member_{$tontine->id}_{$user->id}");

            return redirect()->route('tontines.index')->with('success', 'Vous avez quitté la tontine « '.$tontine->name.' ».');
        } catch (\Throwable $e) {
            Log::error('Erreur départ tontine', ['tontine' => $tontine->id, 'user' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['leave' => 'Erreur lors du départ de la tontine.']);
        }
    }

    public function destroy(Tontine $tontine)
    {
        $this->authorize('delete', $tontine);

        try {
            $tontine->delete();

            return redirect()->route('tontines.index')->with('success', 'Tontine supprimée.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors de la suppression.']);
        }
    }
}
