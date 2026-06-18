<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#009639">
    <title>TontineSN — Mot de passe oublié</title>
    <link href="{{ asset('css/vendor/google-fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/vendor/fontawesome.min.css') }}">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo-img" style="font-size:48px;line-height:1">🔑</div>
            <h1 class="auth-title">Mot de passe oublié</h1>
            <p class="auth-subtitle">Entrez votre email pour recevoir un lien de réinitialisation.</p>
        </div>

        @if(session('status'))
            <div class="auth-alert auth-alert-success">
                <i class="fas fa-check-circle me-1"></i>{{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label fw-semibold">Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" name="email" class="form-control form-control-lg"
                           placeholder="vous@exemple.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-paper-plane me-2"></i>Envoyer le lien
            </button>
        </form>

        <p class="text-center mt-4 mb-0 text-muted small">
            <a href="{{ route('auth.login') }}" class="text-green fw-semibold">
                <i class="fas fa-arrow-left me-1"></i>Retour à la connexion
            </a>
        </p>

    </div>
</div>

</body>
</html>
