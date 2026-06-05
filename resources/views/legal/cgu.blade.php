@extends('layouts.app')

@section('title', 'Conditions Générales d\'Utilisation')

@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-4">Conditions Générales d'Utilisation</h4>

    <div class="card mb-4">
        <h6 class="fw-semibold">1. Objet</h6>
        <p class="text-muted small">Les présentes CGU régissent l'accès et l'utilisation de la plateforme TontineSN, service digital de gestion de tontines.</p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">2. Définitions</h6>
        <p class="text-muted small">
            <strong>Tontine</strong> : association rotative d'épargne et de crédit.<br>
            <strong>Utilisateur</strong> : toute personne inscrite sur la plateforme.<br>
            <strong>Créateur</strong> : utilisateur ayant créé une tontine.<br>
            <strong>Membre</strong> : utilisateur ayant rejoint une tontine.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">3. Inscription et compte</h6>
        <p class="text-muted small">L'utilisateur s'engage à fournir des informations exactes. Chaque compte est personnel et non transférable.</p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">4. Fonctionnement des tontines</h6>
        <p class="text-muted small">
            Les tontines sont gérées de manière collaborative par leurs membres. TontineSN fournit l'infrastructure technique et les outils de suivi.
            La plateforme ne détient pas les fonds : les transactions sont traitées via PayTech.sn ou en espèces.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">5. Paiements</h6>
        <p class="text-muted small">
            Les paiements mobiles (Wave, Orange Money, Free Money) sont traités par PayTech.sn. Les paiements en espèces sont gérés hors ligne par les membres.
            TontineSN n'est pas responsable des délais de traitement des opérateurs de mobile money.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">6. Responsabilité</h6>
        <p class="text-muted small">
            TontineSN agit comme un registre numérique. La responsabilité de la plateforme est limitée à la fourniture du service technique.
            Les litiges entre membres d'une tontine relèvent de leur propre responsabilité.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">7. Données personnelles</h6>
        <p class="text-muted small">
            Les données personnelles sont traitées conformément à notre Politique de Confidentialité.
            Conformément à la règlementation BCEAO, les données sont conservées 5 ans après la dernière activité.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">8. Modification des CGU</h6>
        <p class="text-muted small">TontineSN se réserve le droit de modifier les présentes CGU. Les utilisateurs seront informés de tout changement significatif.</p>
    </div>
</div>
@endsection
