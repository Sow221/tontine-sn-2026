@extends('layouts.app')
@section('title', 'Transactions')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Transactions</h4>
        <span class="badge bg-secondary ms-1">{{ $transactions->total() }}</span>
        <a href="{{ route('admin.transactions.export') }}" class="btn btn-sm btn-outline-primary rounded-pill ms-auto">
            <i class="fas fa-download me-1"></i>Export CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.transactions') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-6 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous les statuts</option>
                    @foreach(['pending' => 'En attente', 'success' => 'Payé', 'failed' => 'Échoué', 'reversed' => 'Annulé'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="method" class="form-select form-select-sm">
                    <option value="">Tous les moyens</option>
                    @foreach(['wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'card' => 'Carte', 'cash' => 'Espèces'] as $val => $label)
                    <option value="{{ $val }}" {{ request('method') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="suspicious" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <option value="1" {{ request('suspicious') ? 'selected' : '' }}>⚠️ Montant élevé</option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
            </div>
        </div>
        @if(request()->filled('status') || request()->filled('method') || request()->filled('suspicious'))
        <a href="{{ route('admin.transactions') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer
        </a>
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
                    @if($tx->amount > config('tontine.transaction.daily_limit'))
                    <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">⚠️ Élevé</span>
                    @endif
                </p>
                <small class="text-muted">
                    {{ $tx->cycle->tontine->name ?? '—' }} ·
                    {{ match($tx->method) { 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'card' => 'Carte', 'cash' => 'Espèces', default => ucfirst($tx->method) } }} ·
                    {{ $tx->created_at->format('d/m/Y H:i') }}
                </small>
            </div>
            <div class="text-end flex-shrink-0">
                <span class="fw-bold {{ $tx->status === 'success' ? 'text-green' : ($tx->status === 'failed' ? 'text-danger' : 'text-warning') }}">
                    {{ number_format($tx->amount, 0, ',', ' ') }} F
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
