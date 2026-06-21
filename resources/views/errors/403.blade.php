<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Accès refusé</title>
    <link href="{{ asset('css/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
    <style>
        .err-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .err-card { background: white; border-radius: 16px; border: 1px solid var(--gray-border); box-shadow: var(--shadow-lg); padding: 48px 32px; max-width: 420px; width: 100%; text-align: center; }
        .err-icon { width: 72px; height: 72px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px; }
        .err-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 24px; }
        body.dark-mode .err-card { background: #1e293b; border-color: #334155; }
        body.dark-mode .err-icon { background: #3b1f1f; }
    </style>
</head>
<body class="bg-off-white">
<div class="err-container">
    <div class="err-card">
        <div class="err-icon">🔒</div>
        <h2 class="fw-bold mb-2">Accès refusé</h2>
        <p class="text-muted mb-1">Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
        @if(auth()->check() && auth()->user()->isAdmin())
            <p class="text-muted small mb-2">Connecté en tant qu'<strong>Administrateur</strong>. Utilisez votre espace d'administration.</p>
        @endif
        <div class="err-actions">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord admin
                    </a>
                    <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-user me-2"></i>Mon profil
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </a>
                    <a href="{{ route('tontines.explore') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-compass me-2"></i>Explorer les tontines
                    </a>
                @endif
            @else
                <a href="{{ route('auth.login') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                </a>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="fas fa-home me-2"></i>Page d'accueil
                </a>
            @endauth
        </div>
    </div>
</div>
</body>
</html>
