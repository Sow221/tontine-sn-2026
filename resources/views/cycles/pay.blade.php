@extends('layouts.app')

@section('title', 'Payer ma cotisation')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.show', $cycle->tontine) }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Payer ma cotisation</h4>
    </div>

    {{-- Récapitulatif --}}
    <div class="card mb-4">
        <div class="text-center py-3">
            <p class="text-muted mb-1">Montant à payer</p>
            <h2 class="fw-bold text-green">{{ number_format($cycle->tontine->amount, 0, ',', ' ') }} FCFA</h2>
            <p class="text-muted small">{{ $cycle->tontine->name }} · Cycle {{ $cycle->cycle_number }}</p>
            <p class="text-muted small">Date limite : {{ $cycle->due_date->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Choix méthode --}}
    <form method="POST" action="{{ route('cycles.pay.initiate', $cycle) }}" x-data="{ method: 'paytech' }">
        @csrf

        <h6 class="fw-semibold mb-3">Choisir le mode de paiement</h6>

        <div class="payment-methods mb-4">

            <label class="payment-option" :class="{ 'selected': method === 'paytech' }">
                <input type="radio" name="method" value="paytech" x-model="method" class="d-none">
                <div class="d-flex align-items-center gap-3">
                    <div class="payment-logo" style="background:#009639; color:white; font-weight:800; font-size:11px;">PAY</div>
                    <div>
                        <p class="fw-semibold mb-0">Paiement mobile</p>
                        <small class="text-muted">Wave · Orange Money · Free Money</small>
                    </div>
                    <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'paytech'"></i>
                </div>
            </label>

            <label class="payment-option" :class="{ 'selected': method === 'cash' }">
                <input type="radio" name="method" value="cash" x-model="method" class="d-none">
                <div class="d-flex align-items-center gap-3">
                    <div class="payment-logo cash-logo"><i class="fas fa-money-bill"></i></div>
                    <div>
                        <p class="fw-semibold mb-0">Espèces</p>
                        <small class="text-muted">Remise en main propre à la gérante</small>
                    </div>
                    <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'cash'"></i>
                </div>
            </label>

        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-lock me-2"></i>Confirmer le paiement
        </button>

        <p class="text-center text-muted small mt-3">
            <i class="fas fa-shield-alt me-1"></i>Paiement sécurisé via PayTech · TLS 1.3
        </p>
    </form>

</div>
@endsection
