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
            <img src="{{ asset('images/Tontine.png') }}" alt="TontineSN" class="auth-logo-img">
            <p class="auth-subtitle">Cotiser simple. Recevoir sûr.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-1"></i>{{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login.post') }}" autocomplete="on">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold" for="email">Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" name="email" id="email"
                           class="form-control form-control-lg @error('email') is-invalid @enderror"
                           placeholder="vous@exemple.com"
                           value="{{ old('email') }}"
                           autocomplete="email"
                           required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="password">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" id="password"
                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                           placeholder="••••••••"
                           autocomplete="current-password"
                           required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember">Se souvenir de moi</label>
                </div>
                <a href="{{ route('password.request') }}" class="small text-green text-decoration-none">
                    Mot de passe oublié ?
                </a>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100">
                <i class="fab fa-google me-2"></i>Continuer avec Google
            </a>
        </div>

        <p class="text-center text-muted small mt-4">
            Pas encore de compte ?
            <a href="{{ route('auth.register') }}" class="text-green fw-semibold text-decoration-none">S'inscrire</a>
        </p>



    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
