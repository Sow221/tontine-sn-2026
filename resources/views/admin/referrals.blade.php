@extends('layouts.app')
@section('title', 'Statistiques de parrainage')

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-green">Admin</a></li>
            <li class="breadcrumb-item active">Parrainage</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Statistiques de parrainage</h4>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-green">{{ $totalReferrals }}</div>
                <div class="stat-label">Inscrits via parrainage</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-indigo">{{ $convertedReferrals }}</div>
                <div class="stat-label">Ont payé au moins une fois</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-warning">{{ $conversionRate }}%</div>
                <div class="stat-label">Taux de conversion</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-green">{{ $topReferrers->count() }}</div>
                <div class="stat-label">Parrains actifs</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-header mb-3">
            <h6 class="section-header__title mb-0">🏅 Top parrains</h6>
        </div>

        @forelse($topReferrers as $i => $user)
        <div class="leaderboard-row mb-1">
            <span class="leaderboard-row__rank">
                @if($i === 0) 🥇 @elseif($i === 1) 🥈 @elseif($i === 2) 🥉 @else {{ $i + 1 }}. @endif
            </span>
            <div class="leaderboard-row__avatar">{{ strtoupper(substr($user->name ?? '?', 0, 2)) }}</div>
            <div class="flex-grow-1 min-width-0">
                <div class="leaderboard-row__name">{{ $user->name }}</div>
                <small class="text-muted">{{ $user->email }} · Code : <code>{{ $user->referral_code }}</code></small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="badge badge-success">{{ $user->referrals_count }} filleul(s)</span>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-xs btn-outline-secondary rounded-pill">Voir</a>
            </div>
        </div>
        @empty
        <div class="empty-state py-4">
            <div class="empty-state__icon">🤝</div>
            <p class="empty-state__desc text-muted">Aucun parrainage enregistré pour le moment.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
