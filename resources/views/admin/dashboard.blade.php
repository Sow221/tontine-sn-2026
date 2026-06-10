@extends('layouts.app')
@section('title', 'Administration')

@section('content')
@push('head-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold mb-0">🛡️ Tableau de bord</h4>
        <span class="badge bg-danger px-3 py-2">Administrateur</span>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-green">{{ number_format($stats['total_users']) }}</div>
                <div class="stat-label">Utilisateurs</div>
                <small class="text-muted">{{ $stats['active_users'] }} actifs</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-indigo">{{ $stats['active_tontines'] }}</div>
                <div class="stat-label">Tontines actives</div>
                <small class="text-muted">{{ $stats['pending_tontines'] }} en attente</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value text-warning">{{ $stats['total_transactions'] >= 1000000 ? number_format($stats['total_transactions'] / 1000000, 1) . 'M' : number_format($stats['total_transactions'] / 1000, 0) . 'K' }}</div>
                <div class="stat-label">FCFA collectés</div>
                <small class="text-muted">transactions réussies</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value {{ $stats['pending_kyc'] > 0 ? 'text-danger' : 'text-muted' }}">
                    {{ $stats['pending_kyc'] }}
                </div>
                <div class="stat-label">KYC en attente</div>
                @if($stats['pending_kyc'] > 0)
                <small class="text-warning">{{ $stats['pending_kyc'] }} document(s) à vérifier</small>
                @endif
            </div>
        </div>
    </div>

    {{-- Exports rapides --}}
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('admin.users.export') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="fas fa-download me-1"></i>Export utilisateurs CSV
        </a>
        <a href="{{ route('admin.transactions.export') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="fas fa-download me-1"></i>Export transactions CSV
        </a>
    </div>

    {{-- Actions rapides --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.users') }}" class="card text-center text-decoration-none py-3">
                <i class="fas fa-users fs-3 text-indigo mb-2"></i>
                <p class="fw-semibold mb-0 small">Utilisateurs</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.tontines') }}" class="card text-center text-decoration-none py-3">
                <i class="fas fa-layer-group fs-3 text-green mb-2"></i>
                <p class="fw-semibold mb-0 small">Tontines</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.transactions') }}" class="card text-center text-decoration-none py-3">
                <i class="fas fa-exchange-alt fs-3 text-warning mb-2"></i>
                <p class="fw-semibold mb-0 small">Transactions</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.stats') }}" class="card text-center text-decoration-none py-3">
                <i class="fas fa-chart-line fs-3 text-danger mb-2"></i>
                <p class="fw-semibold mb-0 small">Statistiques</p>
            </a>
        </div>
    </div>

    {{-- Chart transactions --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Transactions (6 derniers mois)</h6>
        <div style="position:relative;height:160px;">
            <canvas id="adminChart"></canvas>
        </div>
    </div>

    {{-- Tontines bloquées (cycle overdue > 7 jours) — alerte proactive --}}
    @if($blockedTontines->isNotEmpty())
    <div class="card mb-4 border-danger">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Tontines bloquées ({{ $blockedTontines->count() }})</h6>
            <a href="{{ route('admin.tontines', ['status' => 'active']) }}" class="text-muted small">Voir tout</a>
        </div>
        <p class="text-muted small mb-3">Ces tontines ont un cycle en retard depuis plus de 7 jours sans résolution.</p>
        @foreach($blockedTontines as $t)
        <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="member-avatar avatar-sm bg-danger text-white">{{ strtoupper(substr($t->name, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $t->name }}</p>
                <small class="text-muted">Créateur : {{ $t->creator->name ?? '—' }}</small>
            </div>
            <a href="{{ route('admin.tontines.show', $t) }}" class="btn btn-sm btn-outline-danger rounded-pill">
                <i class="fas fa-eye me-1"></i>Voir
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- KYC en attente — actionnables --}}
    @if($pendingKycUsers->isNotEmpty())
    <div class="card mb-4 border-warning">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0 text-warning"><i class="fas fa-id-card me-2"></i>KYC en attente ({{ $pendingKycUsers->count() }})</h6>
            <a href="{{ route('admin.users', ['kyc' => 'pending']) }}" class="text-muted small">Voir tout</a>
        </div>
        @foreach($pendingKycUsers as $u)
        <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="member-avatar avatar-sm">{{ strtoupper(substr($u->name ?? $u->email, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $u->name ?? $u->email }}</p>
                <small class="text-muted">{{ $u->email }}</small>
            </div>
            <a href="{{ route('admin.users.kyc.review', $u) }}" class="btn btn-sm btn-warning rounded-pill">
                <i class="fas fa-search me-1"></i>Vérifier
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tontines récentes --}}
    <div class="d-flex justify-content-between mb-3">
        <h6 class="fw-semibold">Tontines récentes</h6>
        <a href="{{ route('admin.tontines') }}" class="text-green small">Voir tout</a>
    </div>
    @forelse($recentTontines as $t)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.tontines.show', $t) }}" class="tontine-avatar text-decoration-none text-white">{{ strtoupper(substr($t->name, 0, 2)) }}</a>
            <div class="flex-grow-1">
                <a href="{{ route('admin.tontines.show', $t) }}" class="text-decoration-none">
                    <p class="mb-0 fw-semibold small">{{ $t->name }}</p>
                </a>
                <small class="text-muted">{{ $t->creator->name ?? '—' }} · {{ $t->created_at->diffForHumans() }}</small>
            </div>
            <span class="badge badge-{{ $t->status === 'active' ? 'success' : ($t->status === 'suspended' ? 'danger' : 'warning') }}">
                {{ match($t->status) { 'active' => 'Active', 'suspended' => 'Suspendue', 'completed' => 'Terminée', default => 'En attente' } }}
            </span>
            @if($t->status === 'active')
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                    x-data
                    @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.tontines.suspend', $t) }}', message: 'Suspendre « {{ $t->name }} » ?', confirmText: 'Oui, suspendre', type: 'danger' } }))">
                <i class="fas fa-pause"></i>
            </button>
            @elseif($t->status === 'suspended')
            <form method="POST" action="{{ route('admin.tontines.reactivate', $t) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill">
                    <i class="fas fa-play"></i>
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <p class="text-muted small">Aucune tontine récente.</p>
    @endforelse

    {{-- Transactions suspectes — actionnables --}}
    @if($suspiciousTx->isNotEmpty())
    <h6 class="fw-semibold mt-4 mb-3 text-danger">⚠️ Transactions suspectes (montant élevé)</h6>
    @foreach($suspiciousTx as $tx)
    <div class="card mb-2 py-2 border-danger">
        <div class="d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $tx->user->name ?? $tx->user->phone_number ?? '—' }}</p>
                <small class="text-muted">{{ $tx->cycle->tontine->name ?? '—' }} · {{ $tx->created_at->format('d/m/Y H:i') }}</small>
            </div>
            <span class="fw-bold text-danger">{{ number_format($tx->amount, 0, ',', ' ') }} F</span>
            <span class="badge badge-{{ $tx->status === 'success' ? 'success' : ($tx->status === 'pending' ? 'warning' : 'secondary') }}">
                {{ ucfirst($tx->status) }}
            </span>
            @if($tx->status === 'pending')
            <button type="button" class="btn btn-sm btn-success rounded-pill"
                    x-data
                    @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.transactions.force-confirm', $tx) }}', message: 'Confirmer manuellement cette transaction ?', confirmText: 'Oui, confirmer', type: 'danger' } }))">
                <i class="fas fa-check"></i>
            </button>
            @endif
        </div>
    </div>
    @endforeach
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('adminChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartMonths),
            datasets: [{
                label: 'FCFA collectés',
                data: @json($chartAmounts),
                backgroundColor: 'rgba(239,51,64,0.15)',
                borderColor: '#EF3340',
                borderWidth: 2,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => (v/1000).toFixed(0) + 'K', color: '#94a3b8' },
                    grid: { color: 'rgba(148,163,184,0.1)' },
                },
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
@endsection
