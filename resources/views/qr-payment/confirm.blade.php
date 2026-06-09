@extends('layouts.app')
@section('title', 'Confirmer le paiement')

@section('content')
<div class="container py-4" style="max-width:480px;">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item active">Confirmer le paiement</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-1">Confirmer le paiement</h4>
    <p class="text-muted small mb-4">Vérifiez les détails avant de confirmer.</p>

    <div class="card mb-4">
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-green-light rounded-3">
            <div class="icon-box bg-green" style="background:var(--green)!important;">
                <i class="fas fa-qrcode text-white fs-5"></i>
            </div>
            <div>
                <p class="fw-bold mb-0 text-green">Demande de paiement P2P</p>
                <small class="text-muted">Scannée depuis TontineSN</small>
            </div>
        </div>

        <div class="border rounded-3 p-3 mb-4">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Bénéficiaire</span>
                <span class="fw-semibold">{{ $recipient?->name ?? '—' }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Montant</span>
                <span class="fw-bold text-green fs-5">{{ number_format($paymentData['amount'], 0, ',', ' ') }} FCFA</span>
            </div>
            @if(!empty($paymentData['description']))
            <div class="d-flex justify-content-between">
                <span class="text-muted small">Description</span>
                <span class="fw-semibold small">{{ $paymentData['description'] }}</span>
            </div>
            @endif
        </div>

        <form method="POST" action="{{ route('qr-payment.confirm', $token) }}">
            @csrf
            @if($errors->any())
            <div class="alert alert-danger py-2 mb-3 small">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-check me-2"></i>Confirmer le paiement de {{ number_format($paymentData['amount'], 0, ',', ' ') }} FCFA
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost w-100">
                <i class="fas fa-times me-2"></i>Annuler
            </a>
        </form>
    </div>

    <div class="card">
        <p class="small text-muted mb-0">
            <i class="fas fa-shield-alt text-green me-1"></i>
            Ce paiement est sécurisé et traçable. Il sera enregistré dans votre historique TontineSN.
        </p>
    </div>

</div>
@endsection
