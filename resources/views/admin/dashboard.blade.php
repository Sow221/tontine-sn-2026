@extends('layouts.app')

@section('title', 'Administration')

@section('content')
<div class="container py-4">

    <h4 class="fw-bold mb-4">🛡️ Tableau de bord Admin</h4>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-card">
                <div class="stat-value text-green">{{ number_format($stats['total_users']) }}</div>
                <div class="stat-label">Utilisateurs</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-card">
                <div class="stat-value text-indigo">{{ $stats['active_tontines'] }}</div>
                <div class="stat-label">Tontines actives</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-card">
                <div class="stat-value text-warning">{{ number_format($stats['total_transactions'] / 1000, 0) }}K</div>
                <div class="stat-label">FCFA collectés</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-card">
                <div class="stat-value text-danger">{{ $stats['pending_kyc'] }}</div>
                <div class="stat-label">KYC en attente</div>
            </div>
        </div>
    </div>

    {{-- Tontines récentes --}}
    <div class="d-flex justify-content-between mb-3">
        <h6 class="fw-semibold">Tontines récentes</h6>
        <a href="#" class="text-green small">Voir tout</a>
    </div>

    @foreach($recentTontines as $t)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="tontine-avatar">{{ strtoupper(substr($t->name, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $t->name }}</p>
                <small class="text-muted">{{ $t->creator->name ?? '—' }} · {{ $t->created_at->diffForHumans() }}</small>
            </div>
            <span class="badge badge-{{ $t->status === 'active' ? 'success' : 'warning' }}">{{ $t->status }}</span>
        </div>
    </div>
    @endforeach

    {{-- Transactions suspectes --}}
    @if($suspiciousTx->isNotEmpty())
    <h6 class="fw-semibold mt-4 mb-3 text-danger">⚠️ Transactions suspectes</h6>
    @foreach($suspiciousTx as $tx)
    <div class="card mb-2 py-2 border-danger">
        <div class="d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $tx->user->name ?? $tx->user->phone_number }}</p>
                <small class="text-muted">{{ $tx->cycle->tontine->name ?? '—' }}</small>
            </div>
            <span class="fw-bold text-danger">{{ number_format($tx->amount, 0, ',', ' ') }} F</span>
        </div>
    </div>
    @endforeach
    @endif

    {{-- Navigation admin --}}
    <div class="row g-3 mt-4">
        <div class="col-6">
            <a href="{{ route('admin.users') }}" class="card text-center text-decoration-none">
                <i class="fas fa-users fs-3 text-indigo mb-2"></i>
                <p class="fw-semibold mb-0 small">Utilisateurs</p>
            </a>
        </div>
        <div class="col-6">
            <a href="{{ route('admin.logs') }}" class="card text-center text-decoration-none">
                <i class="fas fa-list-alt fs-3 text-indigo mb-2"></i>
                <p class="fw-semibold mb-0 small">Journaux</p>
            </a>
        </div>
    </div>

</div>
@endsection
