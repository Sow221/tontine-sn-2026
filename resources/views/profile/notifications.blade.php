@extends('layouts.app')
@section('title', 'Préférences de notification')

@section('content')
<div class="container py-4">

    <a href="{{ route('profile.show') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>Mon profil
    </a>

    <h4 class="fw-bold mb-4">Préférences de notification</h4>

    <div class="card mb-4">
        <form method="POST" action="{{ route('profile.notifications.update') }}">
            @csrf @method('PUT')

            @php $s = $user->notification_settings ?? []; @endphp

            {{-- Bénéficiaire --}}
            <p class="fw-semibold small mb-2">🎉 Quand c'est votre tour de recevoir</p>
            <div class="form-check mb-1">
                <input type="checkbox" name="settings[beneficiary_email]" class="form-check-input" value="1"
                    {{ ($s['beneficiary_email'] ?? true) ? 'checked' : '' }} id="ben_email">
                <label class="form-check-label small" for="ben_email">Email</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="settings[beneficiary_whatsapp]" class="form-check-input" value="1"
                    {{ ($s['beneficiary_whatsapp'] ?? true) ? 'checked' : '' }} id="ben_wa">
                <label class="form-check-label small" for="ben_wa">WhatsApp</label>
            </div>

            {{-- Paiement confirmé --}}
            <p class="fw-semibold small mb-2">✅ Confirmation de paiement</p>
            <div class="form-check mb-1">
                <input type="checkbox" name="settings[payment_email]" class="form-check-input" value="1"
                    {{ ($s['payment_email'] ?? true) ? 'checked' : '' }} id="pay_email">
                <label class="form-check-label small" for="pay_email">Email</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="settings[payment_whatsapp]" class="form-check-input" value="1"
                    {{ ($s['payment_whatsapp'] ?? true) ? 'checked' : '' }} id="pay_wa">
                <label class="form-check-label small" for="pay_wa">WhatsApp</label>
            </div>

            {{-- Rappels --}}
            <p class="fw-semibold small mb-2">🔔 Rappels avant échéance</p>
            <div class="form-check mb-1">
                <input type="checkbox" name="settings[reminder_email]" class="form-check-input" value="1"
                    {{ ($s['reminder_email'] ?? true) ? 'checked' : '' }} id="rem_email">
                <label class="form-check-label small" for="rem_email">Email</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="settings[reminder_whatsapp]" class="form-check-input" value="1"
                    {{ ($s['reminder_whatsapp'] ?? true) ? 'checked' : '' }} id="rem_wa">
                <label class="form-check-label small" for="rem_wa">WhatsApp</label>
            </div>

            {{-- Nouveau cycle --}}
            <p class="fw-semibold small mb-2">📅 Démarrage d'un nouveau cycle</p>
            <div class="form-check mb-1">
                <input type="checkbox" name="settings[cycle_email]" class="form-check-input" value="1"
                    {{ ($s['cycle_email'] ?? true) ? 'checked' : '' }} id="cyc_email">
                <label class="form-check-label small" for="cyc_email">Email</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="settings[cycle_whatsapp]" class="form-check-input" value="1"
                    {{ ($s['cycle_whatsapp'] ?? true) ? 'checked' : '' }} id="cyc_wa">
                <label class="form-check-label small" for="cyc_wa">WhatsApp</label>
            </div>

            {{-- Adhésion approuvée --}}
            <p class="fw-semibold small mb-2">👋 Approbation de membre</p>
            <div class="form-check mb-1">
                <input type="checkbox" name="settings[member_email]" class="form-check-input" value="1"
                    {{ ($s['member_email'] ?? true) ? 'checked' : '' }} id="mem_email">
                <label class="form-check-label small" for="mem_email">Email</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="settings[member_whatsapp]" class="form-check-input" value="1"
                    {{ ($s['member_whatsapp'] ?? true) ? 'checked' : '' }} id="mem_wa">
                <label class="form-check-label small" for="mem_wa">WhatsApp</label>
            </div>

            {{-- Nouvelle demande d'adhésion (créateur) --}}
            <p class="fw-semibold small mb-2">👤 Demande pour rejoindre ma tontine</p>
            <div class="form-check mb-1">
                <input type="checkbox" name="settings[member_request_email]" class="form-check-input" value="1"
                    {{ ($s['member_request_email'] ?? true) ? 'checked' : '' }} id="mreq_email">
                <label class="form-check-label small" for="mreq_email">Email</label>
            </div>
            <div class="form-check mb-4">
                <input type="checkbox" name="settings[member_request_whatsapp]" class="form-check-input" value="1"
                    {{ ($s['member_request_whatsapp'] ?? true) ? 'checked' : '' }} id="mreq_wa">
                <label class="form-check-label small" for="mreq_wa">WhatsApp</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i>Enregistrer mes préférences
            </button>
        </form>
    </div>

</div>
@endsection
