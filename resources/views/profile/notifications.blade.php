@extends('layouts.app')
@section('title', 'Préférences de notification')

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('profile.show') }}" class="text-green">Mon profil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Notifications</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Préférences de notification</h4>

    <div class="card mb-4">
        <form method="POST" action="{{ route('profile.notifications.update') }}">
            @csrf @method('PUT')

            @php
                $settings = $user->notification_settings ?? [];
            @endphp

            <div class="mb-3">
                <label class="fw-semibold small d-block mb-2">Rappels de paiement</label>
                <div class="form-check mb-2">
                    <input type="checkbox" name="settings[payment_reminder_email]" class="form-check-input" value="1"
                        {{ ($settings['payment_reminder_email'] ?? true) ? 'checked' : '' }} id="pref_email">
                    <label class="form-check-label small" for="pref_email">Recevoir un rappel par email</label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="settings[payment_reminder_sms]" class="form-check-input" value="1"
                        {{ ($settings['payment_reminder_sms'] ?? false) ? 'checked' : '' }} id="pref_sms">
                    <label class="form-check-label small" for="pref_sms">Recevoir un rappel par SMS</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-semibold small d-block mb-2">Nouveaux messages dans le chat</label>
                <div class="form-check mb-2">
                    <input type="checkbox" name="settings[chat_email]" class="form-check-input" value="1"
                        {{ ($settings['chat_email'] ?? true) ? 'checked' : '' }} id="pref_chat">
                    <label class="form-check-label small" for="pref_chat">Être notifié des nouveaux messages par email</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-semibold small d-block mb-2">Activité des tontines</label>
                <div class="form-check mb-2">
                    <input type="checkbox" name="settings[member_joined]" class="form-check-input" value="1"
                        {{ ($settings['member_joined'] ?? true) ? 'checked' : '' }} id="pref_joined">
                    <label class="form-check-label small" for="pref_joined">Un nouveau membre rejoint une tontine</label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="settings[cycle_draw]" class="form-check-input" value="1"
                        {{ ($settings['cycle_draw'] ?? true) ? 'checked' : '' }} id="pref_draw">
                    <label class="form-check-label small" for="pref_draw">Résultat du tirage au sort</label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="settings[payment_confirmation]" class="form-check-input" value="1"
                        {{ ($settings['payment_confirmation'] ?? true) ? 'checked' : '' }} id="pref_payment">
                    <label class="form-check-label small" for="pref_payment">Confirmation de paiement</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i>Enregistrer mes préférences
            </button>
        </form>
    </div>

</div>
@endsection
