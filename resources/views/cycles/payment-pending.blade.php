@extends('layouts.app')

@section('title', 'Paiement en cours...')

@section('content')
<div class="container py-5">
    <div class="text-center" style="max-width:400px; margin:0 auto;">

        {{-- Spinner --}}
        <div id="state-pending">
            <div class="mb-4" style="position:relative; width:80px; height:80px; margin:0 auto;">
                <svg viewBox="0 0 80 80" style="width:80px;height:80px;">
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#E8F5E9" stroke-width="6"/>
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#009639" stroke-width="6"
                        stroke-dasharray="60 154" stroke-linecap="round"
                        style="transform-origin:center; animation:spin 1.2s linear infinite;">
                    </circle>
                </svg>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:24px;">💸</div>
            </div>
            <h4 class="fw-bold mb-2">Paiement en cours...</h4>
            <p class="text-muted mb-1">Votre paiement est en cours de confirmation.</p>
            <p class="text-muted small">Ne fermez pas cette page.</p>

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
            <h4 class="fw-bold mb-2 text-green">Paiement confirmé !</h4>
            <p class="text-muted mb-4">Votre cotisation a bien été enregistrée.</p>
            <a href="#" id="success-link" class="btn btn-primary btn-lg w-100">
                Voir ma tontine <i class="fas fa-arrow-right ms-2"></i>
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
<script>
const statusUrl  = "{{ route('payment.status', $transaction) }}";
const maxRetries = 20; // 20 × 3s = 60s max
let   retries    = 0;
let   countdown  = 3;

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

// Démarrer après 3s
updateCountdown();
setTimeout(checkStatus, 3000);
</script>
@endpush

@endsection
