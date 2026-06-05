<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Page introuvable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card text-center">
        <div style="font-size:4rem;">🌿</div>
        <h2 class="fw-bold mt-3">Page introuvable</h2>
        <p class="text-muted">Cette page n'existe pas ou vous n'avez pas accès à cette ressource.</p>
        @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary rounded-pill px-4 mt-2">
            Retour au dashboard
        </a>
        @else
        <a href="{{ route('auth.login') }}" class="btn btn-primary rounded-pill px-4 mt-2">
            Se connecter
        </a>
        @endauth
    </div>
</div>
</body>
</html>
