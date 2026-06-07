@extends('layouts.app')
@section('title', 'Historique')

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Historique</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-1">
        <h4 class="fw-bold mb-0">Historique des paiements</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('historique.export') . '?' . http_build_query(request()->query()) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-download me-1"></i>CSV
            </a>
            <a href="{{ route('historique.export.pdf') . '?' . http_build_query(request()->query()) }}" class="btn btn-sm btn-outline-danger rounded-pill">
                <i class="fas fa-file-pdf me-1"></i>PDF
            </a>
        </div>
    </div>
    <p class="text-muted small mb-4">
        Total {{ request()->hasAny(['tontine_id','status','periode']) ? 'filtré' : 'cotisé' }} :
        <strong class="text-green">{{ number_format($totalSuccess, 0, ',', ' ') }} FCFA</strong>
    </p>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('historique.index') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-4">
                <select name="tontine_id" class="form-select form-select-sm">
                    <option value="">Toutes les tontines</option>
                    @foreach($tontines as $t)
                    <option value="{{ $t->id }}" {{ request('tontine_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="periode" class="form-select form-select-sm">
                    <option value="">Toutes les périodes</option>
                    @foreach($periodes as $p)
                    <option value="{{ $p['value'] }}" {{ request('periode') === $p['value'] ? 'selected' : '' }}>
                        {{ $p['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous statuts</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Payé</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>Échoué</option>
                </select>
            </div>
            <div class="col-2 col-sm-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>
        @if(request()->hasAny(['tontine_id', 'status', 'periode']))
        <div class="d-flex align-items-center gap-2 mt-2">
            <span class="filter-active-badge">
                <i class="fas fa-filter" aria-hidden="true"></i>Filtres actifs
            </span>
            <a href="{{ route('historique.index') }}" class="filter-clear-link">
                <i class="fas fa-times" aria-hidden="true"></i>Effacer
            </a>
        </div>
        @endif
    </form>

    @if($transactions->isEmpty())
        <div class="text-center py-5">
            <div class="empty-scene">📭💸</div>
            <p class="text-muted fw-semibold">Aucune transaction</p>
            <p class="text-muted small">Les paiements que vous effectuerez apparaîtront ici.</p>
        </div>
    @else
        @foreach($transactions as $tx)
        <div class="card mb-3 py-2">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box {{ $tx->status === 'success' ? 'bg-green-light' : ($tx->status === 'failed' ? 'bg-red-light' : 'bg-yellow-light') }}">
                    <i class="fas fa-{{ $tx->status === 'success' ? 'check-circle text-green' : ($tx->status === 'failed' ? 'times-circle text-danger' : 'clock text-warning') }} fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="mb-0 fw-semibold small">{{ $tx->cycle->tontine->name ?? '—' }}</p>
                    <small class="text-muted">
                        Cycle {{ $tx->cycle->cycle_number ?? '—' }} ·
                        {{ $tx->paid_at?->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }} ·
                        {{ match($tx->method) { 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'card' => 'Carte', 'cash' => 'Espèces', default => ucfirst($tx->method) } }}
                    </small>
                </div>
                <div class="text-end">
                    <span class="fw-bold {{ $tx->status === 'success' ? 'text-green' : ($tx->status === 'failed' ? 'text-danger' : 'text-warning') }}">
                        {{ number_format($tx->amount, 0, ',', ' ') }} F
                    </span>
                    <br>
                    <div class="mt-1"><x-transaction-status :status="$tx->status" /></div>
                    @if($tx->status === 'success')
                    <div class="mt-1">
                        <a href="{{ route('transactions.receipt', $tx) }}" class="small text-green text-decoration-none" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>{{ __('member.download_receipt') }}
                        </a>
                        @if($tx->isReversible())
                        <form method="POST" action="{{ route('transactions.reverse', $tx) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="small text-danger border-0 bg-transparent p-0 ms-2"
                                    onclick="return confirm('Annuler ce paiement ?');">
                                <i class="fas fa-undo me-1"></i>Annuler
                            </button>
                        </form>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-center mt-3">
            {{ $transactions->links() }}
        </div>
    @endif

</div>
@endsection
