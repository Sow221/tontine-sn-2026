@extends('layouts.app')
@section('title', 'Foire aux questions')

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">FAQ</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Foire aux questions</h4>

    <div class="accordion" id="faqAccordion">

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    Qu'est-ce que TontineSN ?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    TontineSN est une plateforme digitale qui permet de créer, gérer et participer à des tontines en ligne.
                    Que ce soit pour l'épargne collective ou les enchères, notre plateforme facilite la gestion des cotisations,
                    le suivi des cycles et les paiements sécurisés entre membres.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Comment créer une tontine ?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Connectez-vous à votre compte, cliquez sur « Créer une tontine » dans le menu, puis remplissez
                    les informations nécessaires : nom, montant par cycle, fréquence, nombre de participants, etc.
                    Une fois créée, vous recevrez un code d'invitation à partager avec les membres.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    Comment rejoindre une tontine ?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Vous pouvez rejoindre une tontine en utilisant le code d'invitation partagé par le créateur.
                    Allez dans « Rejoindre une tontine » depuis l'application, saisissez le code, puis attendez
                    l'approbation du créateur.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    Quels sont les modes de paiement acceptés ?
                </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Nous acceptons Wave, Orange Money, Free Money, les paiements par carte bancaire, et les espèces
                    pour les paiements en personne. Chaque tontine peut spécifier les méthodes acceptées.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                    Comment fonctionne le tirage au sort ?
                </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Dans une tontine à tirage aléatoire, un bénéficiaire est tiré au sort à chaque cycle.
                    Le tirage est transparent et se fait directement depuis l'application. Seuls les membres
                    ayant cotisé pour le cycle en cours participent au tirage.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                    Puis-je quitter une tontine ?
                </button>
            </h2>
            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Oui, vous pouvez quitter une tontine à tout moment depuis la page de la tontine.
                    Notez que la suppression de votre compte nécessite d'avoir quitté toutes vos tontines actives au préalable.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                    Comment mon score crédit est-il calculé ?
                </button>
            </h2>
            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Votre score crédit (sur 10) est basé sur votre régularité de paiement, le nombre de cycles
                    auxquels vous avez participé, le montant total cotisé, et votre ancienneté sur la plateforme.
                    Un bon score vous donne accès à des tontines plus avantageuses.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                    Mes données sont-elles sécurisées ?
                </button>
            </h2>
            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Absolument. Nous utilisons le chiffrement SSL pour toutes les communications, vos mots de passe
                    sont hachés, et nous respectons les réglementations sur la protection des données.
                    Vous pouvez demander la suppression de vos données à tout moment.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                    Que faire en cas de problème de paiement ?
                </button>
            </h2>
            <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Si vous rencontrez un problème lors d'un paiement, vérifiez d'abord votre solde. Si le problème persiste,
                    contactez notre support via l'onglet d'aide. Vous pouvez également réessayer le paiement depuis
                    la page de la tontine concernée.
                </div>
            </div>
        </div>

        <div class="accordion-item mb-2 border rounded-3">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                    Comment contacter le support ?
                </button>
            </h2>
            <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                    Vous pouvez nous contacter par email à <a href="mailto:support@tontine.sn">support@tontine.sn</a>.
                    Nous nous efforçons de répondre à toutes les demandes sous 24 à 48 heures ouvrées.
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
