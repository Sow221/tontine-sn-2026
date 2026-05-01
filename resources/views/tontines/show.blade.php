@extends('layouts.app')

@section('title', $tontine->name)

@section('content')
<div class="container py-4">

    {{-- En-tête --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.index') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-0">{{ $tontine->name }}</h4>
            <small class="text-muted">Code : <strong>{{ $tontine->code }}</strong></small>
        </div>
        <span class="badge badge-{{ $tontine->status === 'active' ? 'success' : 'warning' }} fs-6">
            {{ ucfirst($tontine->status) }}
        </span>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stat-card text-center">
                <div class="stat-value text-green">{{ number_format($tontine->amount, 0, ',', ' ') }}</div>
                <div class="stat-label">FCFA / cycle</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card text-center">
                <div class="stat-value text-indigo">{{ $tontine->activeMembers()->count() }}</div>
                <div class="stat-label">Membres</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card text-center">
                <div class="stat-value text-warning">{{ $tontine->cycles()->count() }}</div>
                <div class="stat-label">Cycles</div>
            </div>
        </div>
    </div>

    {{-- Cycle actuel --}}
    @if($currentCycle)
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Cycle {{ $currentCycle->cycle_number }} en cours</h6>
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar bg-green" style="width: {{ $currentCycle->completionRate() }}%"></div>
        </div>
        <div class="d-flex justify-content-between small text-muted">
            <span>{{ number_format($currentCycle->total_collected, 0, ',', ' ') }} FCFA collectés</span>
            <span>{{ $currentCycle->completionRate() }}%</span>
        </div>
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('cycles.pay', $currentCycle) }}" class="btn btn-primary flex-grow-1">
                <i class="fas fa-money-bill-wave me-2"></i>Payer ma cotisation
            </a>
            @if(auth()->id() === $tontine->created_by && !$currentCycle->beneficiary_id)
            <form method="POST" action="{{ route('cycles.draw', $currentCycle) }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-random"></i>
                </button>
            </form>
            @endif
        </div>
    </div>
    @endif

    {{-- Activation (si en attente) --}}
    @if($tontine->status === 'pending' && auth()->id() === $tontine->created_by)
    <div class="card mb-4 border-warning">
        <p class="text-muted mb-2">La tontine est en attente. Activez-la pour démarrer les cycles.</p>
        <form method="POST" action="{{ route('tontines.activate', $tontine) }}">
            @csrf
            <button type="submit" class="btn btn-warning w-100">
                <i class="fas fa-play me-2"></i>Activer la tontine
            </button>
        </form>
    </div>
    @endif

    {{-- Membres --}}
    <h6 class="fw-semibold mb-3">Membres ({{ $tontine->members->count() }})</h6>
    @foreach($tontine->members as $member)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="member-avatar">{{ strtoupper(substr($member->name ?? $member->phone_number, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $member->name ?? $member->phone_number }}</p>
                <small class="text-muted">Position {{ $member->pivot->position ?? '—' }}</small>
            </div>
            <span class="badge badge-{{ $member->pivot->status === 'active' ? 'success' : 'warning' }}">
                {{ ucfirst($member->pivot->status) }}
            </span>
        </div>
    </div>
    @endforeach

</div>
@endsection
