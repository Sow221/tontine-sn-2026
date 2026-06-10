@extends('layouts.app')

@section('title', 'Payer ma cotisation')

@section('content')
<div class="container py-4">

    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tontines.show', $cycle->tontine) }}" class="text-green">{{ $cycle->tontine->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Paiement</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Payer ma cotisation</h4>

    {{-- Récapitulatif --}}
    <div class="card mb-4">
        <div class="text-center py-3">
            <p class="text-muted mb-1">Montant à payer</p>
            <div class="mb-2">
                <span class="pot-highlight">
                    <i class="fas fa-coins" aria-hidden="true"></i>
                    {{ number_format($totalAmount, 0, ',', ' ') }} FCFA
                </span>
            </div>
            <p class="text-muted small mb-1">{{ $cycle->tontine->name }} · Cycle {{ $cycle->cycle_number }}</p>
            <p class="text-muted small mb-0">Date limite : <strong>{{ $cycle->due_date->format('d/m/Y') }}</strong></p>
            @if($penalty > 0)
            <div class="alert alert-warning mt-3 mb-0 text-start">
                <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                <strong>Pénalité de retard incluse :</strong>
                {{ number_format($cycle->tontine->amount, 0, ',', ' ') }} FCFA
                + {{ number_format($penalty, 0, ',', ' ') }} FCFA ({{ $cycle->tontine->penalty_rate }}%)
            </div>
            @endif
        </div>
    </div>

    @error('payment')
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('cycles.pay.initiate', $cycle) }}" x-data="{ method: 'wave', submitting: false }">
        @csrf

        <h6 class="fw-semibold mb-3">Choisir le mode de paiement</h6>

        <div class="payment-methods mb-3">

            {{-- Wave --}}
            <label class="payment-option" :class="method === 'wave' ? 'payment-wave' : ''">
                <input type="radio" name="method" value="wave" x-model="method" class="d-none">
                <div class="d-flex align-items-center gap-3">
                    <div class="payment-logo wave-logo">
                        <img src="{{ asset('images/logo wave.webp') }}" alt="Wave" class="pay-method-icon">
                    </div>
                    <div>
                        <p class="fw-semibold mb-0">Wave</p>
                        <small class="text-muted">Paiement instantané</small>
                    </div>
                    <template x-if="method === 'wave'"><span class="badge bg-success ms-auto">Recommandé</span></template>
                    <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'wave'"></i>
                </div>
            </label>

            {{-- Orange Money --}}
            <label class="payment-option" :class="method === 'orange_money' ? 'payment-orange' : ''">
                <input type="radio" name="method" value="orange_money" x-model="method" class="d-none">
                <div class="d-flex align-items-center gap-3">
                    <div class="payment-logo om-logo">
                        <img src="{{ asset('images/logo orange money.webp') }}" alt="Orange Money" class="pay-method-icon">
                    </div>
                    <div>
                        <p class="fw-semibold mb-0">Orange Money</p>
                        <small class="text-muted">Paiement mobile</small>
                    </div>
                    <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'orange_money'"></i>
                </div>
            </label>

            {{-- Carte bancaire --}}
            <label class="payment-option" :class="method === 'card' ? 'payment-card' : ''">
                <input type="radio" name="method" value="card" x-model="method" class="d-none">
                <div class="d-flex align-items-center gap-3">
                    <div class="payment-logo card-logo">
                        <img src="{{ asset('images/carte bancaire.webp') }}" alt="Carte" class="pay-method-icon">
                    </div>
                    <div>
                        <p class="fw-semibold mb-0">Carte bancaire</p>
                        <small class="text-muted">Visa / Mastercard</small>
                    </div>
                    <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'card'"></i>
                </div>
            </label>

            {{-- Free Money --}}
            <label class="payment-option" :class="method === 'free_money' ? 'payment-free' : ''">
                <input type="radio" name="method" value="free_money" x-model="method" class="d-none">
                <div class="d-flex align-items-center gap-3">
                    <div class="payment-logo" style="background:#E8F5E9;border:1.5px solid #E2E8F0;">
                        <span class="fw-bold text-green" style="font-size:11px;">FREE</span>
                    </div>
                    <div>
                        <p class="fw-semibold mb-0">Free Money</p>
                        <small class="text-muted">Paiement mobile Free Sénégal</small>
                    </div>
                    <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'free_money'"></i>
                </div>
            </label>

            {{-- Espèces --}}
            <label class="payment-option" :class="method === 'cash' ? 'payment-cash' : ''">
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

        {{-- Note PayTech --}}
        <div class="alert alert-light d-flex gap-2 align-items-start mb-4" x-show="method !== 'cash'">
            <i class="fas fa-info-circle text-muted mt-1"></i>
            <small class="text-muted">
                Wave, Orange Money et carte bancaire sont traités via
                <strong>PayTech</strong> — plateforme de paiement sécurisée.
                Vous serez redirigé vers leur page de paiement.
            </small>
        </div>

        {{-- Confirmation modal --}}
        <div class="modal fade" id="paymentConfirmModal" tabindex="-1" aria-labelledby="paymentConfirmTitle" x-ref="confirmModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold" id="paymentConfirmTitle">Confirmer le paiement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="fs-1 mb-3">
                            <i class="fas fa-shield-alt text-green"></i>
                        </div>
                        <p class="fw-semibold mb-1">Vous allez payer</p>
                        <h3 class="fw-bold text-green mb-2">{{ number_format($totalAmount, 0, ',', ' ') }} FCFA</h3>
                        <p class="text-muted small mb-0" x-text="{ wave: 'Via Wave', orange_money: 'Via Orange Money', free_money: 'Via Free Money', card: 'Via carte bancaire', cash: 'En espèces' }[method] || method"></p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-5"
                                :disabled="submitting"
                                @click="submitting = true; $el.closest('form').submit()">
                            <span x-show="!submitting">Confirmer</span>
                            <span x-show="submitting">
                                <span class="spinner-border spinner-border-sm me-2"></span>Traitement...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#paymentConfirmModal">
            <i class="fas fa-lock me-2"></i>Payer {{ number_format($totalAmount, 0, ',', ' ') }} FCFA
        </button>

        <p class="text-center text-muted small mt-3">
            <i class="fas fa-shield-alt me-1"></i>Paiement sécurisé · TLS 1.3
        </p>

        {{-- Loading overlay on form submit --}}
        <div id="payment-loading-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
            <div class="text-center p-4" style="background:white;border-radius:16px;max-width:300px;margin:20px;">
                <div class="mb-3" style="font-size:48px;">🔄</div>
                <h5 class="fw-bold mb-2">Redirection vers PayTech...</h5>
                <p class="text-muted mb-0">Ne fermez pas cette page. Vous allez être redirigé vers Wave / Orange Money / Carte.</p>
                <div class="spinner-border text-primary mt-3" role="status"><span class="visually-hidden">Chargement...</span></div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('paymentConfirmModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function () {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) btn.disabled = false;
        });
    }

    // Show loading overlay when payment form submits
    const form = document.querySelector('form[action*="cycles/pay"]');
    if (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const overlay = document.getElementById('payment-loading-overlay');
                if (overlay) {
                    overlay.style.display = 'flex';
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement...';
                }
            }
        });
    }
});
</script>
@endpush
