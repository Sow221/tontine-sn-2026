<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#009639">
    <title>Vérifiez votre email — TontineSN</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-192.svg') }}">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f0fdf4; font-family:'Inter',sans-serif; margin:0; }
        .card { background:#fff; border-radius:16px; padding:40px 32px; max-width:460px; width:100%; box-shadow:0 4px 24px rgba(0,0,0,.08); text-align:center; }
        .icon { font-size:56px; margin-bottom:16px; }
        h1 { font-size:22px; font-weight:700; color:#111827; margin:0 0 8px; }
        p { color:#6b7280; font-size:15px; line-height:1.6; margin:0 0 24px; }
        .btn { display:inline-block; background:#009639; color:#fff; border:none; border-radius:999px; padding:12px 28px; font-size:15px; font-weight:600; cursor:pointer; text-decoration:none; width:100%; box-sizing:border-box; }
        .btn:hover { background:#007a2e; }
        .link { color:#009639; font-size:13px; text-decoration:none; display:block; margin-top:16px; }
        .link:hover { text-decoration:underline; }
        .alert { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 16px; font-size:14px; color:#166534; margin-bottom:20px; }
        .alert-warn { background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">✉️</div>
    <h1>Vérifiez votre email</h1>
    <p>
        Nous avons envoyé un lien de vérification à <strong>{{ auth()->user()->email }}</strong>.
        Cliquez sur ce lien pour activer votre compte TontineSN.
    </p>

    @if(session('status') === 'verification-link-sent' || session('status'))
    <div class="alert">{{ session('status') }}</div>
    @endif

    @if(session('success'))
    <div class="alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-warn">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn">Renvoyer l'email de vérification</button>
    </form>

    <form method="POST" action="{{ route('auth.logout') }}" style="margin-top:8px;">
        @csrf
        <button type="submit" class="btn" style="background:#fff;color:#6b7280;border:1px solid #e5e7eb;margin-top:4px;">
            Se déconnecter
        </button>
    </form>

    <p style="margin-top:20px;font-size:12px;color:#9ca3af;">
        Pas reçu l'email ? Vérifiez vos spams. L'email arrive en moins de 2 minutes.
    </p>
</div>
</body>
</html>
