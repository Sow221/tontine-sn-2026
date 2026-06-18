@extends('layouts.app')
@section('title', $user->name ?? $user->email)

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.users') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Utilisateurs
        </a>
        <h4 class="fw-bold mb-0">Profil utilisateur</h4>
    </div>

    {{-- En-tête profil --}}
    <div class="card mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="member-avatar" style="width:56px;height:56px;font-size:1.4rem;flex-shrink:0;">
                {{ strtoupper(substr($user->name ?? $user->email, 0, 2)) }}
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-0">{{ $user->name ?? '—' }}</h5>
                <p class="text-muted small mb-1">{{ $user->email }}</p>
                @if($user->phone_number)
                <p class="text-muted small mb-1"><i class="fas fa-phone me-1"></i>{{ $user->phone_number }}</p>
                @endif
                <p class="text-muted small mb-0">Inscrit le {{ $user->created_at->isoFormat('D MMMM YYYY') }}</p>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="badge bg-{{ $user->role === 'super_admin' ? 'danger' : ($user->role === 'admin' ? 'warning text-dark' : 'secondary') }}">
                    {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre' } }}
                </span>
                <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                    {{ $user->is_active ? 'Actif' : 'Inactif' }}
                </span>
                @if($user->kyc_verified)
                <span class="badge badge-success"><i class="fas fa-shield-alt me-1"></i>KYC vérifié</span>
                @elseif($user->kyc_status === 'rejected')
                <span class="badge badge-danger"><i class="fas fa-times me-1"></i>KYC refusé</span>
                @elseif($user->kyc_document)
                <span class="badge badge-warning">KYC en attente</span>
                @endif
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-green">{{ number_format($stats['total_paid'] / 1000, 0) }}K</div>
                <div class="stat-label">FCFA cotisés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-indigo">{{ $stats['total_cycles'] }}</div>
                <div class="stat-label">Paiements réussis</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value {{ $stats['late_payments'] > 0 ? 'text-danger' : 'text-success' }}">{{ $stats['late_payments'] }}</div>
                <div class="stat-label">Paiements en retard</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value text-warning">{{ $stats['tontines_created'] }}</div>
                <div class="stat-label">Tontines créées</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Score crédit --}}
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <h6 class="fw-semibold mb-3">Score crédit</h6>
                @if($user->creditScore)
                <div class="text-center mb-3">
                    <div class="fs-1 fw-bold text-green">{{ $user->creditScore->score }}<span class="text-muted fs-5">/10</span></div>
                    <span class="badge bg-{{ $user->creditScore->badgeColor() }}">{{ $user->creditScore->badgeLabel() }}</span>
                </div>
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Paiements à l'heure</span>
                        <span class="fw-semibold">{{ $user->creditScore->on_time_payments }}/{{ $user->creditScore->total_cycles }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Ancienneté</span>
                        <span class="fw-semibold">{{ $user->creditScore->seniority_months }} mois</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Calculé le</span>
                        <span class="fw-semibold">{{ $user->creditScore->calculated_at?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                </div>
                @else
                <p class="text-muted small mb-0">Aucun score calculé.</p>
                @endif
            </div>
        </div>

        {{-- Badges --}}
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <h6 class="fw-semibold mb-3">Badges ({{ $user->badges->count() }})</h6>
                @forelse($user->badges as $badge)
                <span class="badge bg-{{ $badge->tier === 'gold' ? 'warning text-dark' : ($badge->tier === 'silver' ? 'secondary' : 'light text-dark border') }} me-1 mb-1">
                    {{ $badge->icon ?? '🏅' }} {{ $badge->name }}
                </span>
                @empty
                <p class="text-muted small mb-0">Aucun badge obtenu.</p>
                @endforelse
            </div>
        </div>

        {{-- Actions admin --}}
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <h6 class="fw-semibold mb-3">Actions</h6>
                <div class="d-flex flex-column gap-2">
                    @if($user->id !== auth()->id())
                    {{-- Changer le rôle --}}
                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-flex gap-2">
                        @csrf
                        <select name="role" class="form-select form-select-sm">
                            @foreach(['member' => 'Membre', 'admin' => 'Admin', 'super_admin' => 'Super Admin'] as $val => $label)
                            <option value="{{ $val }}" {{ $user->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill flex-shrink-0">Changer</button>
                    </form>
                    {{-- Activer / Désactiver --}}
                    <button type="button"
                            class="btn btn-sm btn-{{ $user->is_active ? 'outline-danger' : 'outline-success' }} w-100 rounded-pill"
                            x-data
                            @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.users.toggle', $user) }}', message: '{{ $user->is_active ? 'Désactiver' : 'Activer' }} cet utilisateur ?', confirmText: 'Oui, {{ $user->is_active ? 'désactiver' : 'activer' }}', type: 'danger' } }))">
                        <i class="fas fa-{{ $user->is_active ? 'ban' : 'check-circle' }} me-1"></i>
                        {{ $user->is_active ? 'Désactiver le compte' : 'Activer le compte' }}
                    </button>
                    {{-- KYC --}}
                    @if($user->kyc_document && !$user->kyc_verified)
                    <a href="{{ route('admin.users.kyc.review', $user) }}"
                       class="btn btn-sm btn-warning w-100 rounded-pill">
                        <i class="fas fa-search me-1"></i>Vérifier le KYC
                    </a>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tontines de l'utilisateur --}}
    <div class="card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0">Tontines ({{ $tontines->count() }})</h6>
        </div>
        @forelse($tontines as $tontine)
        <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="tontine-avatar">{{ strtoupper(substr($tontine->name, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $tontine->name }}</p>
                <small class="text-muted">
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA ·
                    {{ $tontine->active_members_count }} membres ·
                    Rejoint {{ $tontine->pivot->joined_at ? \Carbon\Carbon::parse($tontine->pivot->joined_at)->isoFormat('D MMM YYYY') : '—' }}
                </small>
            </div>
            <span class="badge badge-{{ $tontine->pivot->status === 'active' ? 'success' : ($tontine->pivot->status === 'excluded' ? 'danger' : 'warning') }}">
                {{ ucfirst($tontine->pivot->status) }}
            </span>
            <span class="badge badge-{{ $tontine->status === 'active' ? 'success' : ($tontine->status === 'completed' ? 'secondary' : 'warning') }}">
                {{ match($tontine->status) { 'active' => 'Active', 'completed' => 'Terminée', 'suspended' => 'Suspendue', default => 'En attente' } }}
            </span>
        </div>
        @empty
        <p class="text-muted small mb-0">Aucune tontine.</p>
        @endforelse
    </div>

    {{-- 20 dernières transactions --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Transactions récentes (20 dernières)</h6>
        @forelse($transactions as $tx)
        <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="icon-box {{ $tx->status === 'success' ? 'bg-green-light' : ($tx->status === 'failed' ? 'bg-red-light' : 'bg-yellow-light') }}">
                <i class="fas fa-{{ $tx->status === 'success' ? 'check-circle text-green' : ($tx->status === 'failed' ? 'times-circle text-danger' : 'clock text-warning') }}"></i>
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $tx->cycle->tontine->name ?? '—' }}</p>
                <small class="text-muted d-flex align-items-center gap-1 flex-wrap">
                    Cycle {{ $tx->cycle->cycle_number ?? '—' }} ·
                    {{ $tx->paid_at?->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }} ·
                    @php
                        $opIcons = [
                            'wave'         => 'images/logo wave.webp',
                            'orange_money' => 'images/logo orange money.webp',
                            'free_money'   => 'images/logo free money.svg',
                        ];
                    @endphp
                    @if(isset($opIcons[$tx->method]))
                    <img src="{{ asset($opIcons[$tx->method]) }}" alt="" style="height:18px;width:auto;border-radius:3px;">
                    @else
                    <span class="badge" style="background:{{ match($tx->method) { 'card' => '#eef2ff', 'cash' => '#f0fdf4', default => '#f1f5f9' } }};color:{{ match($tx->method) { 'card' => '#6366f1', 'cash' => '#009639', default => '#64748b' } }};font-size:10px;">
                        <i class="fas fa-{{ match($tx->method) { 'card' => 'credit-card', 'cash' => 'money-bill-wave', default => 'question' } }} me-1"></i>
                        {{ match($tx->method) { 'card' => 'Carte', 'cash' => 'Espèces', default => ucfirst($tx->method) } }}
                    </span>
                    @endif
                </small>
            </div>
            <span class="fw-bold {{ $tx->status === 'success' ? 'text-green' : ($tx->status === 'failed' ? 'text-danger' : 'text-warning') }}">
                {{ number_format($tx->amount, 0, ',', ' ') }} F
            </span>
            @if($tx->status === 'pending')
            <button type="button" class="btn btn-sm btn-success rounded-pill"
                    x-data
                    @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.transactions.force-confirm', $tx) }}', message: 'Confirmer manuellement cette transaction ?', confirmText: 'Oui, confirmer', type: 'danger' } }))">
                <i class="fas fa-check"></i>
            </button>
            @endif
        </div>
        @empty
        <p class="text-muted small mb-0">Aucune transaction.</p>
        @endforelse
    </div>

</div>
@endsection
