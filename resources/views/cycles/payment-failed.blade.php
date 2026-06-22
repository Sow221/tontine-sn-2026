@extends('layouts.app')

@section('title', 'Paiement annulé')

@section('content')
<div class="container py-5">
    <div class="text-center" style="max-width:400px; margin:0 auto;">

        <a href="{{ $cycle ? route('tontines.show', $cycle->tontine) : route('dashboard') }}" class="back-link">
            <i class="fas fa-arrow-left"></i>{{ $cycle ? $cycle->tontine->name : 'Tableau de bord' }}
        </a>

        <div class="mb-4" style="font-size:64px;">❌</div>
        <h4 class="fw-bold mb-2">Paiement annulé</h4>
        <p class="text-muted mb-4">
            Votre paiement a été annulé ou a échoué.<br>
            Aucun montant n'a été débité.
        </p>

        @if($cycle)
        <div class="card mb-4 text-start">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">Tontine</span>
                <span class="fw-semibold small">{{ $cycle->tontine->name }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Montant</span>
                <span class="fw-bold text-danger">{{ number_format($cycle->tontine->amount, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
        <a href="{{ route('cycles.pay', $cycle) }}" class="btn btn-primary btn-lg w-100 mb-3">
            <i class="fas fa-redo me-2"></i>Réessayer
        </a>
        @endif

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100">
            <i class="fas fa-arrow-left me-2"></i>Retour au dashboard
        </a>

    </div>
</div>
@endsection
