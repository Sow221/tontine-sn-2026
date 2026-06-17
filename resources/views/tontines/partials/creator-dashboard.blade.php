{{-- Dashboard financier — visible uniquement par le créateur --}}
@if(auth()->id() === $tontine->created_by && $tontine->status === 'active')
@php
    $totalMembers     = $tontine->members->where('pivot.status', 'active')->count();
    $totalCycles      = $tontine->cycles->count();
    $paidCycles       = $tontine->cycles->where('status', 'paid')->count();
    $totalCollected   = $tontine->cycles->sum('total_collected');
    $expectedTotal    = $tontine->amount * $totalMembers * $totalCycles;
    $globalRate       = $expectedTotal > 0 ? round($totalCollected / $expectedTotal * 100, 1) : 0;

    // Membres n'ayant pas payé le cycle en cours
    $unpaidMembers = $currentCycle
        ? $tontine->members->where('pivot.status', 'active')
            ->filter(fn($m) => !$paidMemberIds->contains($m->id))
        : collect();

    // Prochains bénéficiaires (cycles non payés, pas encore de bénéficiaire ou bénéficiaire désigné)
    $upcomingBeneficiaries = $tontine->cycles
        ->whereNotIn('status', ['paid'])
        ->sortBy('cycle_number')
        ->take(3);

    $totalPot = $tontine->amount * $totalMembers;
@endphp

<div class="card mb-4 border-0" style="background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%);border-left:4px solid #009639 !important;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-green"></i>Vue gestionnaire</h6>
        <span class="badge bg-success">Créateur</span>
    </div>

    {{-- KPIs financiers --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center py-2">
                <div class="stat-value text-green" style="font-size:1.2rem;">{{ number_format($totalCollected / 1000, 0) }}K</div>
                <div class="stat-label">FCFA collectés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center py-2">
                <div class="stat-value text-indigo" style="font-size:1.2rem;">{{ $paidCycles }}/{{ $totalCycles }}</div>
                <div class="stat-label">Cycles terminés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center py-2">
                <div class="stat-value {{ $unpaidMembers->count() > 0 ? 'text-danger' : 'text-green' }}" style="font-size:1.2rem;">
                    {{ $unpaidMembers->count() }}
                </div>
                <div class="stat-label">Non payés ce cycle</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center py-2">
                <div class="stat-value text-warning" style="font-size:1.2rem;">{{ number_format($totalPot / 1000, 0) }}K</div>
                <div class="stat-label">Pot par cycle</div>
            </div>
        </div>
    </div>

    {{-- Barre de progression globale --}}
    <div class="mb-3">
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Progression globale</span>
            <span class="fw-semibold">{{ $globalRate }}%</span>
        </div>
        <div style="height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden;">
            <div style="height:100%;width:{{ $globalRate }}%;background:#009639;border-radius:999px;transition:width 0.4s;"></div>
        </div>
    </div>

    {{-- Membres non payés ce cycle --}}
    @if($currentCycle && $unpaidMembers->isNotEmpty())
    <div class="mb-3">
        <p class="fw-semibold small mb-2 text-danger">
            <i class="fas fa-exclamation-circle me-1"></i>{{ $unpaidMembers->count() }} membre(s) n'ont pas encore payé le cycle {{ $currentCycle->cycle_number }}
        </p>
        <div style="max-height:200px;overflow-y:auto;">
            @foreach($unpaidMembers as $m)
            <div class="d-flex align-items-center gap-2 mb-2 py-1 px-2 rounded" style="background:rgba(239,68,68,0.05);">
                <div class="member-avatar avatar-sm" style="background:#fee2e2;color:#dc2626;">{{ strtoupper(substr($m->name ?? '?', 0, 2)) }}</div>
                <div class="flex-grow-1 min-width-0">
                    <p class="mb-0 small fw-semibold text-truncate">{{ $m->name ?? '—' }}</p>
                    @if($m->phone_number)
                    <small class="text-muted">{{ $m->phone_number }}</small>
                    @endif
                </div>
                <form method="POST" action="{{ route('tontines.members.remind', [$tontine, $m]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0"
                            title="Envoyer un rappel WhatsApp"
                            onclick="return confirm('Envoyer un rappel à {{ addslashes($m->name ?? $m->phone_number) }} ?')">
                        <i class="fas fa-bell" style="font-size:11px;"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        {{-- Rappel groupé --}}
        <form method="POST" action="{{ route('tontines.remind-all', $tontine) }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill"
                    onclick="return confirm('Envoyer un rappel à tous les {{ $unpaidMembers->count() }} membres en retard ?')">
                <i class="fas fa-bell me-1"></i>Relancer tous ({{ $unpaidMembers->count() }})
            </button>
        </form>
    </div>
    @elseif($currentCycle && $unpaidMembers->isEmpty())
    <div class="d-flex align-items-center gap-2 p-2 rounded mb-3" style="background:#dcfce7;">
        <i class="fas fa-check-circle text-green"></i>
        <span class="small fw-semibold text-green">Tous les membres ont payé le cycle {{ $currentCycle->cycle_number }} !</span>
    </div>
    @endif

    {{-- Transactions disputées --}}
    @php
        $disputedTx = \App\Models\Transaction::whereHas('cycle', fn($q) => $q->where('tontine_id', $tontine->id))
            ->where('status', 'disputed')
            ->with('user')
            ->get();
    @endphp
    @if($disputedTx->isNotEmpty())
    <div class="mb-3 p-3 rounded" style="background:#fef2f2;border:1px solid #fecaca;">
        <p class="fw-semibold small mb-2 text-danger">
            <i class="fas fa-flag me-1"></i>{{ $disputedTx->count() }} transaction(s) contestée(s)
        </p>
        @foreach($disputedTx as $dtx)
        <div class="d-flex align-items-center gap-2 mb-1">
            <div class="member-avatar avatar-sm" style="background:#fee2e2;color:#dc2626;">
                {{ strtoupper(substr($dtx->user->name ?? '?', 0, 2)) }}
            </div>
            <div class="flex-grow-1 min-width-0">
                <p class="mb-0 small fw-semibold text-truncate">{{ $dtx->user->name ?? '—' }}</p>
                <small class="text-muted">{{ number_format($dtx->amount, 0, ',', ' ') }} FCFA · Cycle {{ $dtx->cycle->cycle_number ?? '?' }}</small>
            </div>
            <a href="{{ route('historique.index', ['tontine_id' => $tontine->id]) }}" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0" style="font-size:11px;">
                Voir
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Prochains bénéficiaires --}}
    @if($upcomingBeneficiaries->isNotEmpty() && !in_array($tontine->type, ['forced_saving', 'ceremonial']))
    <div>
        <p class="fw-semibold small mb-2"><i class="fas fa-trophy text-warning me-1"></i>Prochains bénéficiaires</p>
        @foreach($upcomingBeneficiaries as $uc)
        @php $benef = $tontine->members->firstWhere('id', $uc->beneficiary_id); @endphp
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-muted small" style="min-width:55px;">Cycle {{ $uc->cycle_number }}</span>
            @if($benef)
                <div class="member-avatar avatar-sm" style="width:22px;height:22px;font-size:9px;">{{ strtoupper(substr($benef->name ?? '?', 0, 2)) }}</div>
                <span class="small fw-semibold">{{ $benef->name }}</span>
                <span class="ms-auto small text-green fw-bold">{{ number_format($totalPot / 1000, 0) }}K FCFA</span>
            @else
                <span class="small text-muted fst-italic">Tirage non effectué</span>
                <span class="ms-auto small text-warning fw-bold">{{ number_format($totalPot / 1000, 0) }}K FCFA</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endif
