@extends('layouts.app')
@section('title', 'Contact & Support — TontineSN')

@section('content')
<div class="container py-5" style="max-width:640px;">

    <a href="{{ route('dashboard') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>Tableau de bord
    </a>

    <div class="text-center mb-5">
        <div style="font-size:2.5rem;">💬</div>
        <h1 class="fw-bold h3 mb-2">Besoin d'aide ?</h1>
        <p class="text-muted">Notre équipe répond dans les 24h ouvrées. Pour les urgences paiement, utilisez WhatsApp.</p>
    </div>

    {{-- Canaux rapides --}}
    <div class="row g-3 mb-5">
        <div class="col-12 col-sm-6">
            <a href="https://wa.me/221781620888?text=Bonjour%20TontineSN%2C%20j%27ai%20besoin%20d%27aide"
               target="_blank" rel="noreferrer"
               class="card text-decoration-none h-100 border-0"
               style="background:#dcfce7;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,150,57,.15)'" onmouseout="this.style.boxShadow=''">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:44px;height:44px;background:#009639;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    </div>
                    <div>
                        <p class="fw-bold mb-0 small">WhatsApp Support</p>
                        <p class="text-muted mb-0" style="font-size:12px;">Réponse rapide · Paiements urgents</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6">
            <a href="mailto:contact@tontinesn.com"
               class="card text-decoration-none h-100 border-0"
               style="background:#eff6ff;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(59,130,246,.15)'" onmouseout="this.style.boxShadow=''">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:44px;height:44px;background:#3b82f6;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-envelope" style="color:white;font-size:18px;"></i>
                    </div>
                    <div>
                        <p class="fw-bold mb-0 small">Email</p>
                        <p class="text-muted mb-0" style="font-size:12px;">contact@tontinesn.com</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- FAQ rapide --}}
    <div class="card mb-4">
        <h5 class="fw-bold mb-3"><i class="fas fa-bolt text-warning me-2"></i>Questions fréquentes</h5>
        <details class="mb-3">
            <summary class="fw-semibold small" style="cursor:pointer;">Mon paiement est en attente depuis plus de 10 minutes</summary>
            <p class="text-muted small mt-2 mb-0">
                Les paiements Wave/Orange Money sont généralement confirmés en moins de 5 minutes.
                Si votre paiement reste en attente au-delà de 15 minutes, contactez-nous sur WhatsApp avec votre
                référence de transaction (visible dans votre historique).
            </p>
        </details>
        <details class="mb-3">
            <summary class="fw-semibold small" style="cursor:pointer;">Je n'arrive pas à rejoindre une tontine avec mon code</summary>
            <p class="text-muted small mt-2 mb-0">
                Vérifiez que le code est bien en majuscules et sans espaces.
                Si le problème persiste, le créateur doit vérifier que la tontine n'est pas pleine et que les demandes sont ouvertes.
            </p>
        </details>
        <details class="mb-3">
            <summary class="fw-semibold small" style="cursor:pointer;">Mon score crédit est à 0 alors que j'ai payé</summary>
            <p class="text-muted small mt-2 mb-0">
                Le score se calcule après votre premier paiement confirmé. Le calcul peut prendre quelques minutes.
                Rafraîchissez votre dashboard. Si le problème persiste, contactez-nous.
            </p>
        </details>
        <details>
            <summary class="fw-semibold small" style="cursor:pointer;">Comment supprimer mon compte ?</summary>
            <p class="text-muted small mt-2 mb-0">
                Rendez-vous dans <strong>Profil → Paramètres → Supprimer mon compte</strong>.
                Cette action est irréversible. Toutes vos données seront effacées conformément à notre
                <a href="{{ route('privacy') }}" class="text-green">politique de confidentialité</a>.
            </p>
        </details>
    </div>

    <div class="text-center text-muted small">
        <a href="{{ route('faq.index') }}" class="text-green">Voir toutes les questions →</a>
    </div>

</div>
@endsection
