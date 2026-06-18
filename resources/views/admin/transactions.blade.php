@extends('layouts.app')
@section('title', 'Gestion des transactions | TontineSN')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Tableau de bord
        </a>
        <h4 class="fw-bold mb-0">Transactions</h4>
        <span class="badge bg-secondary ms-1">{{ $transactions->total() }}</span>
        <a href="{{ route('admin.transactions.export') }}" class="btn btn-sm btn-outline-primary rounded-pill ms-auto">
            <i class="fas fa-download me-1"></i>Export CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.transactions') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Nom, téléphone, ID transaction…" value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous statuts</option>
                    @foreach(['pending' => 'En attente', 'success' => 'Payé', 'failed' => 'Échoué', 'reversed' => 'Annulé'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-2">
                <select name="method" class="form-select form-select-sm">
                    <option value="">Tous moyens</option>
                    @foreach(['wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'card' => 'Carte', 'cash' => 'Espèces'] as $val => $label)
                    <option value="{{ $val }}" {{ request('method') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="{{ request('date_from') }}" title="Du">
            </div>
            <div class="col-6 col-sm-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="{{ request('date_to') }}" title="Au">
            </div>
        </div>
        <div class="row g-2 mt-1">
            <div class="col-6 col-sm-3">
                <select name="suspicious" class="form-select form-select-sm">
                    <option value="">Tous montants</option>
                    <option value="1" {{ request('suspicious') ? 'selected' : '' }}>⚠️ Montant élevé</option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
            </div>
        </div>
        @if(request()->filled('status') || request()->filled('method') || request()->filled('suspicious') || request()->filled('search') || request()->filled('date_from') || request()->filled('date_to'))
        <div class="d-flex align-items-center gap-2 mt-2">
            <span class="filter-active-badge"><i class="fas fa-filter" aria-hidden="true"></i>Filtres actifs</span>
            <a href="{{ route('admin.transactions') }}" class="filter-clear-link"><i class="fas fa-times" aria-hidden="true"></i>Effacer</a>
        </div>
        @endif
    </form>

    @forelse($transactions as $tx)
    <div class="card mb-2 py-2 {{ $tx->amount > config('tontine.transaction.daily_limit') ? 'border-warning' : '' }}">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box {{ $tx->status === 'success' ? 'bg-green-light' : ($tx->status === 'failed' ? 'bg-red-light' : 'bg-yellow-light') }}">
                <i class="fas fa-{{ $tx->status === 'success' ? 'check-circle text-green' : ($tx->status === 'failed' ? 'times-circle text-danger' : 'clock text-warning') }} fs-5"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <p class="mb-0 fw-semibold small text-truncate">
                    {{ $tx->user->name ?? $tx->user->email ?? '—' }}
                    @if($tx->user->phone_number ?? false)
                    <span class="text-muted fw-normal"> · {{ $tx->user->phone_number }}</span>
                    @endif
                    @if($tx->amount > config('tontine.transaction.daily_limit'))
                    <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">⚠️ Élevé</span>
                    @endif
                </p>
                <small class="text-muted d-flex align-items-center gap-2 flex-wrap">
                    {{ $tx->cycle->tontine->name ?? '—' }} ·
                    @php
                        $operatorIcons = [
                            'wave'         => ['img' => 'images/logo wave.webp', 'alt' => 'Wave'],
                            'orange_money' => ['img' => 'images/logo orange money.webp', 'alt' => 'Orange Money'],
                            'free_money'   => ['img' => 'images/logo free money.svg', 'alt' => 'Free Money'],
                            'card'         => ['img' => null, 'icon' => 'fas fa-credit-card', 'color' => '#6366f1', 'bg' => '#eef2ff'],
                            'cash'         => ['img' => null, 'icon' => 'fas fa-money-bill-wave', 'color' => '#009639', 'bg' => '#f0fdf4'],
                        ];
                        $icon = $operatorIcons[$tx->method] ?? ['img' => null, 'icon' => 'fas fa-question', 'color' => '#64748b', 'bg' => '#f1f5f9'];
                    @endphp
                    @if($icon['img'])
                    <img src="{{ asset($icon['img']) }}" alt="{{ $icon['alt'] }}" style="height:22px;width:auto;border-radius:4px;">
                    @else
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:5px;background:{{ $icon['bg'] }};color:{{ $icon['color'] }};font-size:12px;border:1px solid {{ $icon['color'] }}30;"><i class="{{ $icon['icon'] }}"></i></span>
                    @endif
                    · {{ $tx->created_at->format('d/m/Y H:i') }}
                    · <span class="text-muted" style="font-family:monospace;font-size:10px;">#{{ $tx->id }}</span>
                </small>
            </div>
            <div class="text-end flex-shrink-0">
                <span class="fw-bold {{ $tx->status === 'success' ? 'text-green' : ($tx->status === 'failed' ? 'text-danger' : 'text-warning') }}">
                    {{ $tx->status === 'success' ? '+' : ($tx->status === 'failed' ? '' : '') }}{{ number_format($tx->amount, 0, ',', ' ') }} F
                </span>
                @if($tx->status === 'pending')
                <div class="mt-1">
                    <button type="button" class="btn btn-sm btn-success rounded-pill"
                            x-data
                            @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.transactions.force-confirm', $tx) }}', message: 'Confirmer manuellement la transaction #{{ $tx->id }} ?', confirmText: 'Oui, confirmer', type: 'danger' } }))">
                        <i class="fas fa-check me-1"></i>Confirmer
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucune transaction trouvée.</div>
    @endforelse

    <div class="mt-3">{{ $transactions->withQueryString()->links() }}</div>

</div>
@endsection
