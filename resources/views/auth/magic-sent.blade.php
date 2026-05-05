<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Vérifiez votre email</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card text-center">

        <div class="auth-logo">📬</div>
        <h2 class="auth-title">Vérifiez votre email</h2>

        @if($email)
            <p class="auth-subtitle">Un lien de connexion a été envoyé à<br><strong>{{ $email }}</strong></p>
        @else
            <p class="auth-subtitle">Un lien de connexion a été envoyé à votre adresse email.</p>
        @endif

        <div class="alert alert-success mt-3">
            <i class="fas fa-check-circle me-2"></i>
            Le lien est valable <strong>15 minutes</strong>. Vérifiez aussi vos spams.
        </div>

        <a href="{{ route('auth.login') }}" class="btn btn-outline-secondary mt-3 w-100">
            <i class="fas fa-arrow-left me-2"></i>Utiliser un autre email
        </a>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
