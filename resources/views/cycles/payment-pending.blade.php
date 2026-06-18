@extends('layouts.app')

@section('title', 'Paiement en cours...')

@section('content')
<div class="container py-5">
    <div class="text-center" style="max-width:400px; margin:0 auto;">

        {{-- Spinner --}}
        <div id="state-pending">
            <div class="mb-4" style="position:relative; width:80px; height:80px; margin:0 auto;">
                @if($transaction->method === 'cash')
                <div style="font-size:64px;">💵</div>
                @else
                <svg viewBox="0 0 80 80" style="width:80px;height:80px;">
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#E8F5E9" stroke-width="6"/>
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#009639" stroke-width="6"
                        stroke-dasharray="60 154" stroke-linecap="round"
                        style="transform-origin:center; animation:spin 1.2s linear infinite;">
                    </circle>
                </svg>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:24px;">💸</div>
                @endif
            </div>

            @if($transaction->method === 'cash')
            <h4 class="fw-bold mb-2">Remise en espèces enregistrée</h4>
            <div class="alert alert-warning text-start mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>En attente de validation</strong> — Votre remise en espèces doit être confirmée
                par le créateur de la tontine. Vous recevrez une notification dès la validation.
            </div>
            <a href="{{ route('tontines.show', $transaction->cycle->tontine) }}" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-arrow-left me-2"></i>Retour à la tontine
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100">
                Tableau de bord
            </a>
            @else
            <h4 class="fw-bold mb-2">Paiement en cours...</h4>
            <p class="text-muted mb-1">Votre paiement est en cours de confirmation.</p>
            <p class="text-muted small">Ne fermez pas cette page.</p>
            @endif

            <div class="card mt-4 text-start">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Tontine</span>
                    <span class="fw-semibold small">{{ $transaction->cycle->tontine->name }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Montant</span>
                    <span class="fw-bold text-green">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Référence</span>
                    <span class="text-muted small">TontineSN-{{ $transaction->id }}</span>
                </div>
            </div>

            <p class="text-muted small mt-4" id="timer-text">Vérification dans <span id="countdown">3</span>s...</p>
        </div>

        {{-- Succès --}}
        <div id="state-success" style="display:none;">
            <div class="mb-4" style="font-size:64px;">✅</div>
            <h4 class="fw-bold mb-2 text-green">{{ __('member.payment_confirmed') }}</h4>
            <p class="text-muted mb-3">{{ __('member.cash_recorded') }}</p>
            <a href="#" id="receipt-link" class="btn btn-outline-success w-100 mb-2 d-none" target="_blank">
                <i class="fas fa-file-pdf me-2"></i>{{ __('member.download_receipt') }}
            </a>
            <a href="#" id="success-link" class="btn btn-primary btn-lg w-100">
                {{ __('member.my_tontines') }} <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>

        {{-- Échec --}}
        <div id="state-failed" style="display:none;">
            <div class="mb-4" style="font-size:64px;">❌</div>
            <h4 class="fw-bold mb-2">Paiement échoué</h4>
            <p class="text-muted mb-4">Le paiement n'a pas pu être confirmé.</p>
            <a href="{{ route('cycles.pay', $transaction->cycle) }}" class="btn btn-primary btn-lg w-100 mb-3">
                Réessayer
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100">
                Retour au dashboard
            </a>
        </div>

    </div>
</div>

@push('styles')
<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>
@endpush

@push('scripts')
@if($transaction->method !== 'cash')
<script nonce="{{ $cspNonce ?? '' }}">
const statusUrl  = "{{ route('payment.status', $transaction) }}";
const maxRetries = 20; // 20 × 3s = 60s max
let   retries    = 0;
let   countdown  = 3;

// Check if returning from PayTech (URL has paytech_return param)
const urlParams = new URLSearchParams(window.location.search);
const isPayTechReturn = urlParams.has('paytech_return') || document.referrer.includes('paytech.sn');

function updateCountdown() {
    const el = document.getElementById('countdown');
    if (el) el.textContent = countdown;
    if (countdown > 0) {
        countdown--;
        setTimeout(updateCountdown, 1000);
    }
}

function checkStatus() {
    fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('state-pending').style.display = 'none';
                document.getElementById('state-success').style.display = 'block';
                document.getElementById('success-link').href = data.redirect_url;
                if (data.receipt_url) {
                    const receipt = document.getElementById('receipt-link');
                    receipt.href = data.receipt_url;
                    receipt.classList.remove('d-none');
                }
                return;
            }

            if (data.status === 'failed') {
                document.getElementById('state-pending').style.display = 'none';
                document.getElementById('state-failed').style.display  = 'block';
                return;
            }

            // Encore pending
            retries++;
            if (retries >= maxRetries) {
                document.getElementById('timer-text').textContent =
                    'La confirmation prend plus de temps que prévu. Vérifiez votre historique.';
                return;
            }

            countdown = 3;
            updateCountdown();
            setTimeout(checkStatus, 3000);
        })
        .catch(() => {
            retries++;
            if (retries < maxRetries) {
                countdown = 3;
                updateCountdown();
                setTimeout(checkStatus, 3000);
            }
        });
}

// Reprendre le polling si la page redevient visible
document.addEventListener('visibilitychange', () => {
    const el = document.getElementById('state-pending');
    if (document.visibilityState === 'visible'
        && el && el.style.display !== 'none'
        && retries < maxRetries) {
        checkStatus();
    }
});

// Show return from PayTech indicator
if (isPayTechReturn) {
    const timerText = document.getElementById('timer-text');
    if (timerText) {
        timerText.innerHTML = '✅ Retour de PayTech détecté. Vérification en cours...';
        timerText.style.color = '#009639';
    }
    // Start checking immediately
    checkStatus();
} else {
    // Démarrer après 3s
    updateCountdown();
    setTimeout(checkStatus, 3000);
}
</script>
@endif
@endpush

@endsection
