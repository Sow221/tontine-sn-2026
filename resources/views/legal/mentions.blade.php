@extends('layouts.app')

@section('title', 'Mentions Légales')

@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-4">Mentions Légales</h4>

    <div class="card mb-4">
        <h6 class="fw-semibold">Éditeur de la plateforme</h6>
        <p class="text-muted small mb-1">TontineSN est une plateforme digitale éditée par :</p>
        <p class="text-muted small">
            <strong>TontineSN</strong><br>
            Sénégal<br>
            Email : contact@tontine.sn
        </p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">Directeur de la publication</h6>
        <p class="text-muted small">Le responsable de la publication est le fondateur de TontineSN.</p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">Hébergement</h6>
        <p class="text-muted small">La plateforme est hébergée par un prestataire tiers. Les données peuvent être stockées dans ou hors de l'UEMOA.</p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">Propriété intellectuelle</h6>
        <p class="text-muted small">L'ensemble des contenus de la plateforme (textes, logos, code) est la propriété exclusive de TontineSN.</p>
    </div>

    <div class="card mb-4">
        <h6 class="fw-semibold">Contact</h6>
        <p class="text-muted small">Pour toute réclamation ou demande, contactez-nous à contact@tontine.sn</p>
    </div>
</div>
@endsection
