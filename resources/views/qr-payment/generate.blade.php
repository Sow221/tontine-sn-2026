@extends('layouts.app')
@section('title', 'Paiement QR Code')

@section('content')
<div class="container py-4" style="max-width:560px;">

    <a href="{{ route('dashboard') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>Tableau de bord
    </a>

    <h4 class="fw-bold mb-1">Paiement P2P par QR Code</h4>
    <p class="text-muted small mb-4">Générez un QR code pour recevoir un paiement d'un membre de vos tontines.</p>

    <div class="card mb-4">
        <h6 class="fw-semibold mb-3"><i class="fas fa-qrcode me-2 text-green"></i>Créer une demande de paiement</h6>

        <form method="POST" action="{{ route('qr-payment.generate') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold small">Demander à <span class="text-danger">*</span></label>
                <select name="to_user_id" class="form-select @error('to_user_id') is-invalid @enderror" required>
                    <option value="">Sélectionner un membre...</option>
                    @foreach($tontineMembers as $member)
                    <option value="{{ $member->id }}" {{ old('to_user_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->name }}
                    </option>
                    @endforeach
                </select>
                @error('to_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if($tontineMembers->isEmpty())
                <div class="form-text text-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Vous devez être membre actif d'au moins une tontine commune avec le destinataire.
                </div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Montant (FCFA) <span class="text-danger">*</span></label>
                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount') }}" min="100" max="1000000"
                       placeholder="Ex : 25000" required>
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">Description (optionnel)</label>
                <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                       value="{{ old('description') }}" maxlength="255"
                       placeholder="Ex : Remboursement cycle 3">
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100" {{ $tontineMembers->isEmpty() ? 'disabled' : '' }}>
                <i class="fas fa-qrcode me-2"></i>Générer le QR Code
            </button>
        </form>
    </div>

    <div class="card">
        <h6 class="fw-semibold mb-2"><i class="fas fa-info-circle me-2 text-indigo"></i>Comment ça marche ?</h6>
        <ol class="small text-muted ps-3 mb-0">
            <li class="mb-1">Sélectionnez le membre qui doit vous payer</li>
            <li class="mb-1">Entrez le montant et une description optionnelle</li>
            <li class="mb-1">Partagez le QR code ou le lien généré</li>
            <li>Le membre scanne et confirme le paiement</li>
        </ol>
    </div>

</div>
@endsection
