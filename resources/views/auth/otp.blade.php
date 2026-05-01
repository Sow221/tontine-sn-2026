<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Vérification OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">

        <div class="text-center mb-4">
            <div class="auth-logo">🔐</div>
            <h2 class="auth-title">Vérification</h2>
            <p class="auth-subtitle">Code envoyé au <strong>{{ $phone }}</strong></p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('auth.verify-otp') }}" x-data="otpTimer()">
            @csrf

            {{-- Saisie OTP --}}
            <div class="otp-inputs mb-4" id="otp-container">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" class="otp-digit" data-index="{{ $i }}" inputmode="numeric">
                @endfor
                <input type="hidden" name="code" id="otp-hidden">
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                <i class="fas fa-check me-2"></i>Confirmer
            </button>

            <div class="text-center">
                <span x-show="countdown > 0" class="text-muted small">
                    Renvoyer dans <span x-text="countdown" class="fw-bold text-green"></span>s
                </span>
                <a x-show="countdown === 0" href="{{ route('auth.login') }}" class="text-green small">
                    <i class="fas fa-redo me-1"></i>Renvoyer le code
                </a>
            </div>
        </form>

    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/tontine.js') }}"></script>
</body>
</html>
