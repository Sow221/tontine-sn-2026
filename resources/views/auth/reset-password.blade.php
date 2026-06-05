<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Réinitialisation de mot de passe TontineSN">
    <title>TontineSN — Nouveau mot de passe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo">🔒</div>
            <h1 class="fw-bold h3">Nouveau mot de passe</h1>
            <p class="auth-subtitle">Choisissez un mot de passe sécurisé.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label class="form-label" for="reset-email">Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" name="email" id="reset-email" class="form-control form-control-lg"
                           value="{{ old('email', $email) }}" required autocomplete="email">
                </div>
            </div>

            <div class="mb-3" data-pw-container>
                <label class="form-label" for="reset-password">Nouveau mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" id="reset-password" class="form-control form-control-lg"
                           placeholder="8 caractères minimum" minlength="8" required
                           autocomplete="new-password" data-pw-strength>
                    <button type="button" class="btn btn-outline-secondary" data-toggle-pw="reset-password"
                            aria-label="Afficher le mot de passe">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="pw-strength mt-2">
                    <div data-pw-segment class="pw-segment"></div>
                    <div data-pw-segment class="pw-segment"></div>
                    <div data-pw-segment class="pw-segment"></div>
                    <div data-pw-segment class="pw-segment"></div>
                </div>
                <div data-pw-criteria class="pw-criteria"></div>
            </div>

            <div class="mb-4" data-pw-container>
                <label class="form-label" for="reset-password-confirm">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" name="password_confirmation" id="reset-password-confirm"
                           class="form-control form-control-lg"
                           placeholder="••••••••" required autocomplete="new-password"
                           data-pw-match="reset-password">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-pw="reset-password-confirm"
                            aria-label="Afficher le mot de passe">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div data-pw-match-feedback class="mt-1 small"></div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-check me-2"></i>Réinitialiser le mot de passe
            </button>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/tontine.js') }}"></script>
</body>
</html>
