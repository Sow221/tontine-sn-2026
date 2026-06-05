@extends('layouts.app')

@section('title', __('member.payment_recorded'))

@section('content')
<div class="container py-5">
    <div class="text-center" style="max-width:400px; margin:0 auto;">

        <div class="mb-4" style="font-size:64px;"><i class="fas fa-hourglass-half text-warning"></i></div>
        <h4 class="fw-bold mb-2 text-warning">Espèces déclarées — En attente</h4>
        <p class="text-muted mb-1">Votre déclaration de paiement en espèces a été enregistrée.</p>
        <p class="text-muted small mb-4">⏳ Le gérant doit confirmer la réception physique de l'argent avant que votre paiement soit validé.</p>

        <div class="card mb-4 text-start">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">{{ __('member.tontine_label') }}</span>
                <span class="fw-semibold small">{{ $transaction->cycle->tontine->name }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">{{ __('member.cycle_label') }}</span>
                <span class="fw-semibold small">{{ $transaction->cycle->cycle_number }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">{{ __('member.amount_label') }}</span>
                <span class="fw-bold text-green">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">{{ __('member.status') }}</span>
                <x-transaction-status :status="$transaction->status" />
            </div>
        </div>

        @if($transaction->status === 'success')
        <a href="{{ route('transactions.receipt', $transaction) }}" class="btn btn-outline-success w-100 mb-2" target="_blank">
            <i class="fas fa-file-pdf me-2"></i>{{ __('member.download_receipt') }}
        </a>
        @endif

        <a href="{{ route('tontines.show', $transaction->cycle->tontine) }}" class="btn btn-primary btn-lg w-100 mb-3">
            <i class="fas fa-eye me-2"></i>{{ __('member.my_tontines') }}
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100">
            {{ __('member.home') }}
        </a>

    </div>
</div>
@endsection
