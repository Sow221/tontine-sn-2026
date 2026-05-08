@extends('layouts.app')

@section('title', 'Paiement annulé')

@section('content')
<div class="container py-5">
    <div class="text-center" style="max-width:400px; margin:0 auto;">

        <div class="mb-4" style="font-size:64px;">❌</div>
        <h4 class="fw-bold mb-2">Paiement annulé</h4>
        <p class="text-muted mb-4">
            Votre paiement a été annulé ou a échoué.<br>
            Aucun montant n'a été débité.
        </p>

        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg w-100 mb-3">
            <i class="fas fa-arrow-left me-2"></i>Retour au dashboard
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary w-100">
            Réessayer
        </a>

    </div>
</div>
@endsection
