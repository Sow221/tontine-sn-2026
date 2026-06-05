@extends('layouts.app')

@section('title', 'Politique de Confidentialité')

@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-4">Politique de Confidentialité</h4>

    <div class="card mb-4">
        <h6 class="fw-semibold">1. Collecte des données</h6>
        <p class="text-muted small">
            Nous collectons les données suivantes : nom, email, numéro de téléphone, photo de profil, documents KYC,
            informations de paiement, historique des cotisations et score de crédit.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">2. Utilisation des données</h6>
        <p class="text-muted small">
            Les données sont utilisées pour : gérer votre compte, traiter les paiements, calculer votre score de crédit,
            vous envoyer des notifications, et assurer la conformité réglementaire.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">3. Partage des données</h6>
        <p class="text-muted small">
            Vos données personnelles ne sont pas vendues à des tiers. Elles peuvent être partagées avec PayTech.sn
            pour le traitement des paiements, et avec les autorités compétentes si requis par la loi.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">4. Sécurité</h6>
        <p class="text-muted small">
            Les données sont chiffrées en transit (HTTPS) et au repos. L'accès est protégé par authentification et journalisé.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">5. Vos droits</h6>
        <p class="text-muted small">
            Vous pouvez accéder, modifier ou supprimer vos données personnelles depuis votre profil.
            Conformément à la règlementation, vous pouvez demander la portabilité de vos données.
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">6. Conservation</h6>
        <p class="text-muted small">
            Les données sont conservées pendant 5 ans après votre dernière activité, conformément aux obligations BCEAO.
            Après suppression du compte, les données sont anonymisées.
        </p>
    </div>
</div>
@endsection
