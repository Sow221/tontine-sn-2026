<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session expirée — TontineSN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="bg-off-white d-flex align-items-center" style="min-height:100vh;">
    <div class="container text-center py-5">
        <div class="mb-4" style="font-size:5rem;">⏰</div>
        <h1 class="fw-bold mb-3">Session expirée</h1>
        <p class="text-muted mb-4">Votre session a expiré. Veuillez vous reconnecter.</p>
        <a href="{{ route('auth.login') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-sign-in-alt me-2"></i>Se reconnecter
        </a>
    </div>
</body>
</html>
