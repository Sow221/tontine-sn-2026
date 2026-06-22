@extends('layouts.app')
@section('title', 'Gestion des tontines | TontineSN')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Tableau de bord
        </a>
        <h4 class="fw-bold mb-0">Tontines</h4>
        <span class="badge bg-secondary ms-1">{{ $tontines->total() }}</span>
    </div>

    <form method="GET" action="{{ route('admin.tontines') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Nom ou code…" value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous les statuts</option>
                    @foreach(['pending' => 'En attente', 'active' => 'Active', 'suspended' => 'Suspendue', 'completed' => 'Terminée'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Tous les types</option>
                    @foreach(['fixed' => 'Fixe', 'auction' => 'Enchères', 'forced_saving' => 'Épargne', 'ceremonial' => 'Cérémonielle'] as $val => $label)
                    <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i></button>
            </div>
        </div>
        @if(request()->filled('search') || request()->filled('status') || request()->filled('type'))
        <a href="{{ route('admin.tontines') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer
        </a>
        @endif
    </form>

    @forelse($tontines as $tontine)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.tontines.show', $tontine) }}" class="tontine-avatar text-decoration-none text-white">{{ strtoupper(substr($tontine->name, 0, 2)) }}</a>
            <div class="flex-grow-1 min-width-0">
                <a href="{{ route('admin.tontines.show', $tontine) }}" class="text-decoration-none">
                    <p class="mb-0 fw-semibold small text-truncate">{{ $tontine->name }}</p>
                </a>
                <small class="text-muted">
                    {{ $tontine->creator->name ?? '—' }} ·
                    Code : <strong>{{ $tontine->code }}</strong> ·
                    {{ $tontine->active_members_count }} membres ·
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA/cycle ·
                    {{ match($tontine->type) { 'auction' => 'Enchères', 'forced_saving' => 'Épargne', 'ceremonial' => 'Cérémonielle', default => 'Fixe' } }}
                    @if($tontine->pot_total ?? 0 > 0)
                    · <span class="text-green fw-semibold">Pot : {{ number_format($tontine->pot_total, 0, ',', ' ') }} F</span>
                    @endif
                </small>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
                <span class="badge badge-{{ match($tontine->status) { 'active' => 'success', 'suspended' => 'danger', 'completed' => 'secondary', default => 'warning' } }}">
                    {{ match($tontine->status) { 'active' => 'Active', 'suspended' => 'Suspendue', 'completed' => 'Terminée', default => 'En attente' } }}
                </span>
                <div class="d-flex gap-1 mt-1">
                    @if(auth()->user()->isAdmin() && $tontine->status === 'active')
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                            x-data
                            @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.tontines.suspend', $tontine) }}', message: 'Suspendre « {{ addslashes($tontine->name) }} » ?', confirmText: 'Oui, suspendre', type: 'danger' } }))">
                        <i class="fas fa-pause me-1"></i>Suspendre
                    </button>
                    @elseif(auth()->user()->isAdmin() && $tontine->status === 'suspended')
                    <form method="POST" action="{{ route('admin.tontines.reactivate', $tontine) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill">
                            <i class="fas fa-play me-1"></i>Réactiver
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <div style="font-size:2.5rem;">🤝</div>
        <p class="fw-semibold text-muted mb-1">Aucune tontine trouvée</p>
        @if(request()->hasAny(['search','status','type']))
        <small class="text-muted">Essayez d'autres filtres ou <a href="{{ route('admin.tontines') }}">voir toutes les tontines</a>.</small>
        @endif
    </div>
    @endforelse

    <div class="mt-3">{{ $tontines->withQueryString()->links() }}</div>

</div>
@endsection
