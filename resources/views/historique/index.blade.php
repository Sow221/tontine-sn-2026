@extends('layouts.app')
@section('title', 'Historique des paiements | TontineSN')

@section('content')
@php
    $isFiltered = request()->hasAny(['tontine_id','status','periode','operateur','type_flux','date_debut','date_fin','search']);

    $opConfig = [
        'wave'         => ['img' => 'images/logo wave.webp',            'alt' => 'Wave',         'color' => '#00DCA5', 'bg' => '#f0fdf4', 'label' => 'Wave'],
        'orange_money' => ['img' => 'images/logo orange money.webp',    'alt' => 'Orange Money', 'color' => '#FF7900', 'bg' => '#fff7ed', 'label' => 'Orange Money'],
        'free_money'   => ['img' => 'images/logo free money.svg', 'alt' => 'Free Money', 'color' => '#E3000F', 'bg' => '#fef2f2', 'label' => 'Free Money'],
        'card'         => ['img' => null, 'alt' => 'Carte',             'label' => 'Carte',      'color' => '#6366f1', 'bg' => '#eef2ff', 'fa' => 'fa-credit-card'],
        'cash'         => ['img' => null, 'alt' => 'Espèces',           'label' => 'Espèces',    'color' => '#009639', 'bg' => '#f0fdf4', 'fa' => 'fa-money-bill-wave'],
    ];

    $retaitTypes = ['retrait', 'redistribution', 'withdrawal', 'gain'];
@endphp

<div class="container py-4">

    <a href="{{ route('dashboard') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>Tableau de bord
    </a>

    <div class="d-flex justify-content-between align-items-center gap-2 mb-1 flex-wrap">
        <h4 class="fw-bold mb-0">Historique des paiements</h4>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="{{ route('historique.export') . '?' . http_build_query(request()->query()) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-download me-1"></i><span class="d-none d-sm-inline">Export </span>CSV
            </a>
            <a href="{{ route('historique.export.pdf') . '?' . http_build_query(request()->query()) }}" class="btn btn-sm btn-outline-danger rounded-pill">
                <i class="fas fa-file-pdf me-1"></i>PDF
            </a>
        </div>
    </div>
    <p class="text-muted small mb-4">
        Total {{ $isFiltered ? 'filtré' : 'cotisé' }} :
        <strong class="text-green">{{ number_format($totalSuccess, 0, ',', ' ') }} FCFA</strong>
    </p>

    {{-- ── POTS REÇUS (décaissements) ────────────────────────────────── --}}
    @if($decaissements->isNotEmpty())
    <div class="card mb-4 card--accent-green">
        <h6 class="fw-semibold mb-3"><i class="fas fa-hand-holding-usd me-2 text-green"></i>Pots reçus</h6>
        <div class="d-flex flex-column gap-2">
            @foreach($decaissements as $cycle)
            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                <div class="op-icon" style="background:#f0fdf4;border:1px solid rgba(0,150,57,0.2);">
                    <i class="fas fa-trophy" style="color:#009639;"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <p class="mb-0 fw-semibold small text-truncate">{{ $cycle->tontine->name ?? '—' }}</p>
                    <small class="text-muted">Cycle #{{ $cycle->cycle_number }} · {{ $cycle->drawn_at?->format('d/m/Y') ?? '—' }}</small>
                </div>
                <div class="text-end" style="flex-shrink:0;">
                    <span class="fw-bold text-green" style="font-size:15px;">
                        + {{ number_format($cycle->total_collected, 0, ',', ' ') }} FCFA
                    </span>
                    <br>
                    <span class="badge-pot-recu">Pot reçu</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── FILTRES ────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('historique.index') }}" class="card mb-4">

        {{-- Ligne 1 : Recherche --}}
        <div class="mb-2">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0"
                       placeholder="Nom, téléphone ou ID transaction (ex: TXN-984723)…"
                       value="{{ request('search') }}">
            </div>
        </div>

        {{-- Ligne 2 : Filtres principaux --}}
        <div class="row g-2 mb-2">
            <div class="col-6 col-sm-3">
                <select name="tontine_id" class="form-select form-select-sm">
                    <option value="">Toutes les tontines</option>
                    @foreach($tontines as $t)
                    <option value="{{ $t->id }}" {{ request('tontine_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="operateur" class="form-select form-select-sm">
                    <option value="">Tous les opérateurs</option>
                    <option value="wave"         {{ request('operateur') === 'wave'         ? 'selected' : '' }}>Wave</option>
                    <option value="orange_money" {{ request('operateur') === 'orange_money' ? 'selected' : '' }}>Orange Money</option>
                    <option value="free_money"   {{ request('operateur') === 'free_money'   ? 'selected' : '' }}>Free Money</option>
                    <option value="card"         {{ request('operateur') === 'card'         ? 'selected' : '' }}>Carte bancaire</option>
                    <option value="cash"         {{ request('operateur') === 'cash'         ? 'selected' : '' }}>Espèces</option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="type_flux" class="form-select form-select-sm">
                    <option value="">Toutes opérations</option>
                    <option value="cotisation" {{ request('type_flux') === 'cotisation' ? 'selected' : '' }}>📥 Cotisations (entrées)</option>
                    <option value="retrait"    {{ request('type_flux') === 'retrait'    ? 'selected' : '' }}>📤 Retraits / Gains (sorties)</option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous statuts</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>✅ Succès</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ En attente</option>
                    <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>❌ Échoué</option>
                </select>
            </div>
        </div>

        {{-- Ligne 3 : Plage de dates --}}
        <div class="row g-2">
            <div class="col-5 col-sm-3">
                <input type="date" name="date_debut" class="form-control form-control-sm"
                       value="{{ request('date_debut') }}" title="Date début">
            </div>
            <div class="col-auto d-flex align-items-center text-muted small px-0">→</div>
            <div class="col-5 col-sm-3">
                <input type="date" name="date_fin" class="form-control form-control-sm"
                       value="{{ request('date_fin') }}" title="Date fin">
            </div>
            <div class="col-12 col-sm-3">
                <select name="periode" class="form-select form-select-sm">
                    <option value="">Ou choisir un mois…</option>
                    @foreach($periodes as $p)
                    <option value="{{ $p['value'] }}" {{ request('periode') === $p['value'] ? 'selected' : '' }}>{{ $p['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
                @if($isFiltered)
                <a href="{{ route('historique.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </div>
        </div>

        @if($isFiltered)
        <div class="d-flex align-items-center gap-2 mt-2 pt-2 border-top">
            <span class="filter-active-badge"><i class="fas fa-filter" aria-hidden="true"></i>Filtres actifs</span>
            <a href="{{ route('historique.index') }}" class="filter-clear-link"><i class="fas fa-times" aria-hidden="true"></i>Tout effacer</a>
        </div>
        @endif
    </form>

    {{-- ── LISTE DES TRANSACTIONS ─────────────────────────────────────── --}}
    @if($transactions->isEmpty())
        <div class="text-center py-5">
            <div class="empty-scene">📭💸</div>
            <p class="text-muted fw-semibold">Aucune transaction</p>
            <p class="text-muted small">Les paiements que vous effectuerez apparaîtront ici.</p>
        </div>
    @else
        @foreach($transactions as $tx)
        @php
            $op = $opConfig[$tx->method] ?? ['img' => null, 'label' => '?', 'color' => '#64748b', 'bg' => '#f1f5f9', 'alt' => $tx->method];
            $isSortie = in_array($tx->type, $retaitTypes);
            $amountColor = $isSortie ? 'color:#1e293b;' : 'color:#009639;';
            $amountPrefix = $isSortie ? '−' : '+';
            $statusConfig = [
                'success' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'fa-check-circle',  'label' => 'Succès'],
                'pending' => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'fa-clock',          'label' => 'En attente'],
                'failed'  => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'fa-times-circle',   'label' => 'Échoué'],
            ];
            $sc = $statusConfig[$tx->status] ?? $statusConfig['pending'];
        @endphp
        <div class="card mb-3 py-2">
            <div class="d-flex align-items-center gap-3">

                {{-- Logo opérateur --}}
                <div class="op-icon" style="background:{{ $op['bg'] }};border:1px solid {{ $op['color'] }}30;">
                    @if(!empty($op['img']))
                        <img src="{{ asset($op['img']) }}" alt="{{ $op['alt'] }}">
                    @elseif(!empty($op['fa']))
                        <i class="fas {{ $op['fa'] }}" style="color:{{ $op['color'] }};"></i>
                    @else
                        <span style="font-size:10px;font-weight:800;color:{{ $op['color'] }};">{{ $op['label'] }}</span>
                    @endif
                </div>

                {{-- Infos transaction --}}
                <div class="flex-grow-1 min-width-0">
                    <p class="mb-0 fw-semibold small text-truncate">{{ $tx->cycle->tontine->name ?? '—' }}</p>
                    <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                        @if($tx->type === 'redistribution')
                        <span class="badge-tx-type badge-tx-type--pot">Pot</span>
                        @elseif($tx->type === 'cotisation')
                        <span class="badge-tx-type badge-tx-type--cotisation">Cotisation</span>
                        @endif
                        <small class="text-muted">
                            {{ $tx->user->name ?? '—' }}
                            @if($tx->user?->phone)
                            <span class="text-muted opacity-75">· {{ $tx->user->phone }}</span>
                            @endif
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                        @if($tx->external_reference)
                        <code style="font-size:10px;background:#f1f5f9;padding:1px 6px;border-radius:4px;color:#475569;font-family:monospace;">{{ $tx->external_reference }}</code>
                        @endif
                        <small class="text-muted">{{ $tx->paid_at?->format('d/m/Y à H:i') ?? $tx->created_at->format('d/m/Y à H:i') }}</small>
                    </div>
                </div>

                {{-- Montant + statut --}}
                <div class="text-end" style="flex-shrink:0;">
                    <span class="fw-bold" style="{{ $amountColor }}font-size:15px;">
                        {{ $amountPrefix }} {{ number_format($tx->amount, 0, ',', ' ') }} FCFA
                    </span>
                    <br>
                    <span class="tx-status tx-status--{{ $tx->status }}">
                        <i class="fas {{ $sc['icon'] }}" aria-hidden="true"></i>{{ $sc['label'] }}
                    </span>
                    @if($tx->status === 'success')
                    <div class="mt-1">
                        <a href="{{ route('transactions.receipt', $tx) }}" class="small text-green text-decoration-none" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>{{ __('member.download_receipt') }}
                        </a>
                        @if($tx->isReversible())
                        <button type="button" class="small text-danger border-0 bg-transparent p-0 ms-2"
                                x-data
                                @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'confirm-modal', action: '{{ route('transactions.reverse', $tx) }}', message: 'Annuler ce paiement de {{ number_format($tx->amount, 0, ',', ' ') }} FCFA ? Cette action est irréversible.', confirmText: 'Annuler le paiement', type: 'danger' } }))">
                            <i class="fas fa-undo me-1"></i>Annuler
                        </button>
                        @endif
                        @if($tx->method === 'cash' && empty($tx->metadata['disputed']))
                        <button type="button" class="small text-warning border-0 bg-transparent p-0 ms-2"
                                x-data
                                @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'dispute-modal', action: '{{ route('transactions.dispute', $tx) }}', message: 'Contester ce paiement espèces ? Le créateur de la tontine sera notifié.', confirmText: 'Contester', type: 'danger' } }))">
                            <i class="fas fa-flag me-1"></i>Contester
                        </button>
                        @elseif(!empty($tx->metadata['disputed']))
                        <span class="small ms-2" style="color:#f59e0b;"><i class="fas fa-flag me-1"></i>Contesté</span>
                        @endif
                    </div>
                    @endif
                    @if($tx->status === 'pending')
                    <small class="text-warning d-block mt-1"><i class="fas fa-clock me-1"></i>En cours…</small>
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

<x-confirm-modal id="confirm-modal" method="POST" />
<x-confirm-modal id="dispute-modal" method="POST" />

@endsection
