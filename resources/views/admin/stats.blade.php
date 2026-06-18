@extends('layouts.app')
@section('title', 'Statistiques')

@section('content')
@push('head-scripts')
<script src="{{ asset('js/vendor/chart.min.js') }}"></script>
@endpush
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Tableau de bord
        </a>
        <h4 class="fw-bold mb-0">Statistiques plateforme</h4>
    </div>

    {{-- Charts côte à côte --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <h6 class="fw-semibold mb-3">Inscriptions (6 mois)</h6>
                <div style="position:relative;height:200px;">
                    <canvas id="regChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <h6 class="fw-semibold mb-3">Transactions réussies (6 mois)</h6>
                <div style="position:relative;height:200px;">
                    <canvas id="txChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Répartition types tontines --}}
    @php $totalByType = $tontinesByType->sum(); @endphp
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <h6 class="fw-semibold mb-3">Répartition par type de tontine</h6>
                @foreach($tontinesByType as $type => $count)
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="small text-muted" style="width:110px;">
                        {{ match($type) { 'fixed' => 'Fixe', 'auction' => 'Enchères', 'forced_saving' => 'Épargne forcée', 'ceremonial' => 'Cérémonielle', default => ucfirst($type) } }}
                    </span>
                    <div class="flex-grow-1">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-green"
                                 style="width:{{ $totalByType > 0 ? round($count / $totalByType * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <span class="fw-bold small">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <h6 class="fw-semibold mb-3">Top 5 membres actifs</h6>
                @foreach($topMembers as $i => $member)
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="fw-bold {{ $i < 3 ? 'text-warning' : 'text-muted' }}" style="min-width:20px;">
                        @if($i === 0)🥇 @elseif($i === 1)🥈 @elseif($i === 2)🥉 @else {{ $i + 1 }}. @endif
                    </span>
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                         style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
                        {{ strtoupper(substr($member->name ?? '?', 0, 2)) }}
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-0 fw-semibold small">{{ $member->name ?? '—' }}</p>
                        <small class="text-muted">{{ $member->email }}</small>
                    </div>
                    <span class="badge bg-light text-dark border">{{ $member->success_tx_count }} paiements</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function () {
    const months = @json($months);

    new Chart(document.getElementById('regChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Inscriptions',
                data: @json($regData),
                borderColor: '#009639',
                backgroundColor: 'rgba(0,150,57,0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: '#94a3b8', stepSize: 1 }, grid: { color: 'rgba(148,163,184,0.1)' } },
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('txChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'FCFA',
                data: @json($txData),
                backgroundColor: 'rgba(45,47,83,0.15)',
                borderColor: '#2D2F53',
                borderWidth: 2,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => (v/1000).toFixed(0) + 'K', color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,0.1)' } },
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
@endsection
