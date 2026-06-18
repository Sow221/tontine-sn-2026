<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#009639">
    <title>Vérifiez votre email — TontineSN</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-192.svg') }}">
    <link href="{{ asset('css/vendor/google-fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/vendor/fontawesome.min.css') }}">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo-img" style="font-size:56px;line-height:1">✉️</div>
            <h1 class="auth-title">Vérifiez votre email</h1>
            <p class="auth-subtitle">
                Nous avons envoyé un lien de vérification à <strong>{{ auth()->user()->email }}</strong>.
                Cliquez sur ce lien pour activer votre compte TontineSN.
            </p>
        </div>

        @if(session('status') === 'verification-link-sent' || session('status'))
            <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
        @endif

        @if(session('success'))
            <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-paper-plane me-2"></i>Renvoyer l'email de vérification
            </button>
        </form>

        <form method="POST" action="{{ route('auth.logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-outline-secondary w-100 mt-2">
                Se déconnecter
            </button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0">
            Pas reçu l'email ? Vérifiez vos spams. L'email arrive en moins de 2 minutes.
        </p>

    </div>
</div>

</body>
</html>
