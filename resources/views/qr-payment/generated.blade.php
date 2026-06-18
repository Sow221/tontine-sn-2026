@extends('layouts.app')
@section('title', 'QR Code généré')

@section('content')
<div class="container py-4" style="max-width:480px;">

    <a href="{{ route('qr-payment.show') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>Paiement QR
    </a>

    <div class="card text-center mb-4">
        <h5 class="fw-bold mb-1">Votre QR Code de paiement</h5>
        <p class="text-muted small mb-4">
            Partagez ce QR code ou le lien avec
            <strong>{{ $paymentData['to']['name'] }}</strong>
            pour recevoir <strong>{{ number_format($paymentData['amount'], 0, ',', ' ') }} FCFA</strong>.
        </p>

        {{-- QR Code SVG --}}
        <div class="d-flex justify-content-center mb-4">
            <div class="p-3 bg-white border rounded-3" style="display:inline-block;">
                {!! $paymentData['qr_code'] !!}
            </div>
        </div>

        {{-- Lien de paiement --}}
        <div class="bg-light rounded-3 p-3 mb-4 text-start">
            <div class="small text-muted mb-1">Lien de paiement</div>
            <div class="d-flex align-items-center gap-2">
                <code class="flex-grow-1 small text-break">{{ $paymentData['url'] }}</code>
                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                        onclick="copyToClipboard('{{ $paymentData['url'] }}')"
                        aria-label="Copier le lien">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        {{-- Résumé --}}
        <div class="row g-2 mb-4 text-start">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">Destinataire</div>
                    <div class="fw-semibold small">{{ $paymentData['to']['name'] }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">Montant</div>
                    <div class="fw-bold text-green">{{ number_format($paymentData['amount'], 0, ',', ' ') }} F</div>
                </div>
            </div>
        </div>

        {{-- Actions partage --}}
        <div class="d-flex flex-column gap-2">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $paymentData['to']['phone'] ?? '') }}?text={{ urlencode('Bonjour ! Voici mon lien de paiement TontineSN ('.number_format($paymentData['amount'], 0, ',', ' ').' FCFA) : '.$paymentData['url']) }}"
               target="_blank" rel="noreferrer" class="btn btn-success">
                <i class="fab fa-whatsapp me-2"></i>Envoyer sur WhatsApp
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('{{ $paymentData['url'] }}')">
                <i class="fas fa-link me-2"></i>Copier le lien
            </button>
            <a href="{{ route('qr-payment.show') }}" class="btn btn-ghost w-100">
                <i class="fas fa-arrow-left me-2"></i>Nouvelle demande
            </a>
        </div>

        <p class="text-muted small mt-3 mb-0">
            <i class="fas fa-clock me-1"></i>Ce QR code expire dans <strong>1 heure</strong>.
        </p>
    </div>

</div>
@endsection
