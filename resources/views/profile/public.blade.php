@extends('layouts.app')
@section('title', $user->name)

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
        </ol>
    </nav>

    <div class="text-center mb-4">
        @if($user->avatar)
            @php $avatarUrl = str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar); @endphp
            <img src="{{ $avatarUrl }}" class="rounded-circle" width="100" height="100" alt="avatar" style="object-fit:cover">
        @else
            <div class="mx-auto d-flex align-items-center justify-content-center bg-green text-white"
                 style="width:100px;height:100px;border-radius:50%;font-size:2.5rem;font-weight:700;">
                {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
            </div>
        @endif
        <h4 class="fw-bold mt-3 mb-1">{{ $user->name }}</h4>
        <span class="badge badge-{{ match($user->role) { 'super_admin' => 'danger', 'admin' => 'warning', default => 'secondary' } }} fs-6">
            {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre' } }}
        </span>
        <p class="text-muted small mt-2">Membre depuis {{ $user->created_at->isoFormat('MMMM YYYY') }}</p>
    </div>

    <div class="card mb-4">
        <div class="row g-3 text-center">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-value text-indigo fs-4">{{ $activeTontinesCount }}</div>
                    <div class="stat-label">Tontine(s) active(s)</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    @if($user->creditScore)
                    <div class="stat-value text-green fs-4">{{ $user->creditScore->score }}<span class="text-muted fs-6">/10</span></div>
                    <div class="stat-label">Score crédit</div>
                    @else
                    <div class="stat-value text-muted fs-4">—</div>
                    <div class="stat-label">Score crédit</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($user->creditScore)
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Détails du score crédit</h6>
        <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-coins me-1"></i>Montant cotisé</span>
            <span class="fw-semibold">{{ number_format($user->creditScore->total_contributed, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="d-flex justify-content-between small mb-2">
            <span class="text-muted"><i class="fas fa-check me-1"></i>Paiements à l'heure</span>
            <span class="fw-semibold">{{ $user->creditScore->on_time_payments }} / {{ $user->creditScore->total_cycles }}</span>
        </div>
        <div class="d-flex justify-content-between small">
            <span class="text-muted"><i class="fas fa-calendar me-1"></i>Ancienneté</span>
            <span class="fw-semibold">{{ $user->creditScore->seniority_months }} mois</span>
        </div>
        <div class="progress progress-sm mt-3">
            <div class="progress-bar bg-{{ $user->creditScore->badgeColor() === 'light' ? 'secondary' : $user->creditScore->badgeColor() }}"
                 style="width:{{ $user->creditScore->score * 10 }}%"></div>
        </div>
    </div>
    @endif

</div>
@endsection
