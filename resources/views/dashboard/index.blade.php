@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="container py-4">

    {{-- Salutation --}}
    <div class="mb-4">
        <h2 class="fw-bold text-indigo mb-0">
            Bonjour, {{ $user->name ?? 'Membre' }} 👋
        </h2>
        <p class="text-muted small">{{ now()->isoFormat('dddd D MMMM YYYY') }}</p>
    </div>

    {{-- Score crédit --}}
    <div class="card card-score mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="text-muted small mb-1">Mon score crédit</p>
                <h3 class="fw-bold mb-0">{{ $creditScore->score }}<span class="text-muted fs-6">/10</span></h3>
                <span class="badge bg-{{ $creditScore->badgeColor() }} mt-1">{{ $creditScore->badgeLabel() }}</span>
            </div>
            <div class="score-circle">
                <svg viewBox="0 0 36 36" class="score-svg">
                    <path class="score-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="score-fill" stroke-dasharray="{{ $creditScore->score * 10 }}, 100"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <text x="18" y="20.35" class="score-text">{{ $creditScore->score }}</text>
                </svg>
            </div>
        </div>
    </div>

    {{-- Prochain paiement --}}
    @if($nextPayment)
    <div class="card card-warning mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box bg-yellow-light">
                <i class="fas fa-clock text-warning fs-4"></i>
            </div>
            <div class="flex-grow-1">
                <p class="text-muted small mb-0">Prochain paiement</p>
                <h5 class="fw-bold mb-0">{{ number_format($nextPayment->tontine->amount, 0, ',', ' ') }} FCFA</h5>
                <small class="text-muted">{{ $nextPayment->tontine->name }} · {{ $nextPayment->due_date->format('d/m/Y') }}</small>
            </div>
            <a href="{{ route('cycles.pay', $nextPayment) }}" class="btn btn-sm btn-primary rounded-pill">
                Payer
            </a>
        </div>
    </div>
    @endif

    {{-- Mes tontines actives --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Mes tontines</h5>
        <a href="{{ route('tontines.index') }}" class="text-green small">Voir tout</a>
    </div>

    @forelse($activeTontines as $tontine)
    <div class="card mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="tontine-avatar">{{ strtoupper(substr($tontine->name, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <h6 class="fw-semibold mb-0">{{ $tontine->name }}</h6>
                <small class="text-muted">
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA · {{ $tontine->frequency_label ?? $tontine->frequency }}
                </small>
            </div>
            <span class="badge badge-{{ $tontine->status === 'active' ? 'success' : 'warning' }}">
                {{ ucfirst($tontine->status) }}
            </span>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <div class="empty-state-icon">🌱</div>
        <p class="text-muted">Vous n'avez pas encore de tontine.</p>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-plus me-2"></i>Créer une tontine
        </a>
    </div>
    @endforelse

    {{-- Transactions récentes --}}
    @if($recentTransactions->isNotEmpty())
    <h5 class="fw-bold mt-4 mb-3">Transactions récentes</h5>
    @foreach($recentTransactions as $tx)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="method-icon method-{{ $tx->method }}">
                <i class="fas fa-{{ $tx->method === 'wave' ? 'wave-square' : ($tx->method === 'cash' ? 'money-bill' : 'mobile-alt') }}"></i>
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $tx->cycle->tontine->name ?? '—' }}</p>
                <small class="text-muted">{{ $tx->paid_at?->format('d/m/Y H:i') }}</small>
            </div>
            <span class="fw-bold text-green">{{ number_format($tx->amount, 0, ',', ' ') }} F</span>
        </div>
    </div>
    @endforeach
    @endif

</div>
@endsection
