@extends('layouts.app')
@section('title', $tontine->name)

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.tontines') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0 text-truncate">{{ $tontine->name }}</h4>
        <span class="badge badge-{{ match($tontine->status) { 'active' => 'success', 'suspended' => 'danger', 'completed' => 'secondary', default => 'warning' } }} ms-auto flex-shrink-0">
            {{ match($tontine->status) { 'active' => 'Active', 'suspended' => 'Suspendue', 'completed' => 'Terminée', default => 'En attente' } }}
        </span>
    </div>

    {{-- En-tête --}}
    <div class="card mb-4">
        <div class="row g-2 small">
            <div class="col-6">
                <span class="text-muted">Créateur</span><br>
                <strong>{{ $tontine->creator->name ?? '—' }}</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Code</span><br>
                <strong style="letter-spacing:.1em;">{{ $tontine->code }}</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Type</span><br>
                <strong>{{ match($tontine->type) { 'fixed' => 'Fixe', 'auction' => 'Enchères', 'forced_saving' => 'Épargne forcée', 'ceremonial' => 'Cérémonielle', default => ucfirst($tontine->type) } }}</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Fréquence</span><br>
                <strong>{{ match($tontine->frequency) { 'daily' => 'Quotidienne', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuelle', default => $tontine->frequency } }}</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Montant / cycle</span><br>
                <strong>{{ number_format($tontine->amount, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Pénalité retard</span><br>
                <strong>{{ $tontine->penalty_rate > 0 ? $tontine->penalty_rate . '%' : '—' }}</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Date début</span><br>
                <strong>{{ $tontine->start_date->format('d/m/Y') }}</strong>
            </div>
            <div class="col-6">
                <span class="text-muted">Date fin</span><br>
                <strong>{{ $tontine->end_date?->format('d/m/Y') ?? '—' }}</strong>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-indigo">{{ $active_members_count ?? $tontine->active_members_count }}</div>
                <div class="stat-label">Membres actifs</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value {{ ($tontine->pending_members_count ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                    {{ $tontine->pending_members_count ?? 0 }}
                </div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-green">{{ $totalCollected >= 1000 ? number_format($totalCollected / 1000, 0) . 'K' : number_format($totalCollected) }}</div>
                <div class="stat-label">FCFA collectés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-warning">{{ $cyclesPaid }}/{{ $tontine->cycles->count() }}</div>
                <div class="stat-label">Cycles payés</div>
            </div>
        </div>
    </div>

    {{-- Actions admin --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Actions</h6>
        <div class="d-flex gap-2 flex-wrap">
            @if($tontine->status === 'active')
            <button type="button" class="btn btn-outline-danger rounded-pill"
                    x-data
                    @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.tontines.suspend', $tontine) }}', message: 'Suspendre « {{ addslashes($tontine->name) }} » ?', confirmText: 'Oui, suspendre', type: 'danger' } }))">
                <i class="fas fa-pause me-1"></i>Suspendre
            </button>
            @elseif($tontine->status === 'suspended')
            <form method="POST" action="{{ route('admin.tontines.reactivate', $tontine) }}">
                @csrf
                <button type="submit" class="btn btn-outline-success rounded-pill">
                    <i class="fas fa-play me-1"></i>Réactiver
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Membres --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Membres ({{ $members->count() }})</h6>
        @forelse($members as $member)
        <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <a href="{{ route('admin.users.show', $member) }}" class="member-avatar avatar-sm text-decoration-none text-white">
                {{ strtoupper(substr($member->name ?? '?', 0, 2)) }}
            </a>
            <div class="flex-grow-1">
                <a href="{{ route('admin.users.show', $member) }}" class="text-decoration-none">
                    <p class="mb-0 fw-semibold small">{{ $member->name ?? '—' }}</p>
                </a>
                <small class="text-muted">
                    Position {{ $member->pivot->position ?? '—' }} ·
                    Rejoint {{ $member->pivot->joined_at ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('d/m/Y') : '—' }}
                </small>
            </div>
            <span class="badge badge-{{ $member->pivot->status === 'active' ? 'success' : ($member->pivot->status === 'excluded' ? 'danger' : 'warning') }}">
                {{ ucfirst($member->pivot->status) }}
            </span>
            @if($member->id === $tontine->created_by)
            <span class="badge badge-info">Créateur</span>
            @endif
        </div>
        @empty
        <p class="text-muted small mb-0">Aucun membre.</p>
        @endforelse
    </div>

    {{-- Cycles --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Cycles ({{ $tontine->cycles->count() }})</h6>
        @forelse($tontine->cycles as $cycle)
        <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="icon-box bg-light flex-shrink-0">
                <span class="fw-bold text-muted small">{{ $cycle->cycle_number }}</span>
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 small fw-semibold">{{ $cycle->beneficiary->name ?? 'Bénéficiaire non désigné' }}</p>
                <small class="text-muted">
                    Échéance {{ $cycle->due_date->format('d/m/Y') }} ·
                    {{ number_format($cycle->total_collected, 0, ',', ' ') }} FCFA collectés
                </small>
            </div>
            <span class="badge badge-{{ match($cycle->status) { 'paid' => 'success', 'overdue' => 'danger', 'partial' => 'warning', default => 'secondary' } }}">
                {{ match($cycle->status) { 'paid' => 'Payé', 'overdue' => 'En retard', 'partial' => 'Partiel', default => 'En attente' } }}
            </span>
        </div>
        @empty
        <p class="text-muted small mb-0">Aucun cycle généré.</p>
        @endforelse
    </div>

</div>
@endsection
