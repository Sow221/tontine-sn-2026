@extends('layouts.app')
@section('title', 'Mes tontines')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold mb-0">Mes tontines</h4>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-plus me-2"></i>Nouvelle tontine
        </a>
    </div>

    {{-- Recherche + filtre statut --}}
    <form method="GET" action="{{ route('tontines.index') }}" class="mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-7">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Rechercher par nom ou code…"
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-8 col-sm-3">
                <select name="status" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach(['active' => 'Actives', 'pending' => 'En attente', 'completed' => 'Terminées', 'suspended' => 'Suspendues'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-sm-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i></button>
            </div>
        </div>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('tontines.index') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer les filtres
        </a>
        @endif
    </form>

    {{-- Rejoindre via code --}}
    <div class="card mb-4 border-0 bg-light">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box bg-green-light"><i class="fas fa-link text-green fs-5"></i></div>
            <div class="flex-grow-1">
                <p class="fw-semibold mb-0 small">Vous avez un code d'invitation ?</p>
                <small class="text-muted">Rejoignez une tontine existante</small>
            </div>
            <a href="{{ route('tontines.join.form') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                Rejoindre
            </a>
        </div>
    </div>

    @if($tontines->isEmpty())
    <div class="text-center py-5">
        <div class="empty-scene">🤝💼</div>
        <p class="text-muted fw-semibold">Vous n'avez pas encore de tontine</p>
        <p class="text-muted small mb-3">Créez votre première tontine ou rejoignez un groupe existant.</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
                <i class="fas fa-plus me-2"></i>Créer une tontine
            </a>
            <a href="{{ route('tontines.join.form') }}" class="btn btn-outline-secondary rounded-pill">
                Rejoindre avec un code
            </a>
        </div>
    </div>
    @else

    @foreach($tontines as $tontine)
    @php
        $statusCycle = $tontine->currentCycle;
        $gradClass   = match($tontine->type) {
            'auction'       => 'tontine-gradient-auction',
            'ceremonial'    => 'tontine-gradient-ceremonial',
            'forced_saving' => 'tontine-gradient-saving',
            default         => 'tontine-gradient-standard',
        };
        $memberStatus = $tontine->pivot->status;
        $isCreator    = $tontine->created_by === auth()->id();
    @endphp

    <a href="{{ route('tontines.show', $tontine) }}"
       class="card mb-3 text-decoration-none text-dark {{ $gradClass }} {{ $memberStatus === 'pending' ? 'opacity-75' : '' }}"
       style="border-left: 4px solid {{ $isCreator ? '#009639' : '#e2e8f0' }};">
        <div class="d-flex align-items-center gap-3">
            <div class="tontine-avatar">{{ strtoupper(substr($tontine->name, 0, 2)) }}</div>
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h6 class="fw-semibold mb-0 text-truncate">{{ $tontine->name }}</h6>
                    @if($isCreator)
                        <span class="badge badge-success" style="font-size:10px;">👑 Créateur</span>
                    @endif
                    @if($memberStatus === 'pending')
                        <span class="badge badge-warning" style="font-size:10px;">En attente</span>
                    @endif
                </div>
                <small class="text-muted">
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA ·
                    {{ match($tontine->frequency) { 'daily' => 'Quotidienne', 'weekly' => 'Hebdo', 'monthly' => 'Mensuelle', default => $tontine->frequency } }} ·
                    Code : <strong>{{ $tontine->code }}</strong>
                </small>
                @if($statusCycle)
                <div class="mt-1">
                    <small class="text-muted">Cycle {{ $statusCycle->cycle_number }} · Échéance {{ $statusCycle->due_date->format('d/m/Y') }}</small>
                </div>
                @endif
            </div>
            <div class="text-end flex-shrink-0">
                <span class="badge badge-{{ match($tontine->status) { 'active' => 'success', 'completed' => 'secondary', 'suspended' => 'danger', default => 'warning' } }}"
                      role="status" aria-label="Statut : {{ match($tontine->status) { 'active' => 'Active', 'completed' => 'Terminée', 'pending' => 'En attente', 'suspended' => 'Suspendue', default => ucfirst($tontine->status) } }}">
                    {{ match($tontine->status) { 'active' => 'Active', 'completed' => 'Terminée', 'pending' => 'En attente', 'suspended' => 'Suspendue', default => ucfirst($tontine->status) } }}
                </span>
                <div class="small text-muted mt-1">{{ $tontine->active_members_count }} membre(s)</div>
            </div>
        </div>
    </a>
    @endforeach

    <div class="d-flex justify-content-center mt-3">
        {{ $tontines->links() }}
    </div>

    @endif
</div>
@endsection
