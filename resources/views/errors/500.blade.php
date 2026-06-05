<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur — TontineSN</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="bg-off-white d-flex align-items-center" style="min-height:100vh;">
    <div class="container text-center py-5">
        <div class="mb-4" style="font-size:5rem;">😵</div>
        <h1 class="fw-bold mb-3">Erreur interne</h1>
        <p class="text-muted mb-4">Une erreur s'est produite. Nos équipes ont été notifiées.</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="javascript:location.reload()" class="btn btn-primary rounded-pill">
                <i class="fas fa-redo me-2"></i>Réessayer
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-light rounded-pill">
                <i class="fas fa-home me-2"></i>Accueil
            </a>
        </div>
    </div>
</body>
</html>
