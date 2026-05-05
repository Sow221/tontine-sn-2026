<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo">🌿</div>
            <h1 class="auth-title">TontineSN</h1>
            <p class="auth-subtitle">Gérez vos tontines en toute sécurité</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('auth.send-magic-link') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-semibold">Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input
                        type="email"
                        name="email"
                        class="form-control form-control-lg"
                        placeholder="vous@exemple.com"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                </div>
                <div class="form-text">Un lien de connexion vous sera envoyé par email.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-paper-plane me-2"></i>Recevoir le lien
            </button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">
                <a href="?lang=fr" class="text-decoration-none me-2">🇫🇷 Français</a>
                <a href="?lang=wo" class="text-decoration-none me-2">🇸🇳 Wolof</a>
                <a href="?lang=en" class="text-decoration-none">🇬🇧 English</a>
            </small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
