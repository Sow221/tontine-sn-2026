<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Créer un compte</title>
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
            <p class="auth-subtitle">Rejoignez TontineSN gratuitement</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('auth.register.post') }}" autocomplete="on">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold" for="name">Nom complet</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="name" id="name"
                           class="form-control form-control-lg @error('name') is-invalid @enderror"
                           placeholder="Votre nom complet"
                           value="{{ old('name') }}"
                           autocomplete="name" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="phone_number">Téléphone <span class="text-muted fw-normal small">(Wave, Orange Money…)</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true"><i class="fas fa-phone text-muted"></i></span>
                    <input type="tel" name="phone_number" id="phone_number"
                           class="form-control form-control-lg"
                           placeholder="+221 77 000 00 00"
                           value="{{ old('phone_number') }}"
                           autocomplete="tel">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="email">Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" id="email"
                           class="form-control form-control-lg @error('email') is-invalid @enderror"
                           placeholder="vous@exemple.com"
                           value="{{ old('email') }}"
                           autocomplete="email" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="password">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="password"
                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                           placeholder="8 caractères minimum" minlength="8"
                           autocomplete="new-password" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePwd('password', this)"
                            aria-label="Afficher le mot de passe">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="mt-1" style="height:4px;border-radius:2px;background:#e9ecef;overflow:hidden;" role="progressbar" aria-label="Force du mot de passe" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="strength-bar-wrap">
                    <div id="strength-bar" style="height:100%;width:0;transition:all .3s;border-radius:2px;"></div>
                </div>
                <small id="strength-text" class="text-muted" aria-live="polite"></small>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" for="password_confirmation">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-white" aria-hidden="true"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control form-control-lg"
                           placeholder="••••••••"
                           autocomplete="new-password" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePwd('password_confirmation', this)"
                            aria-label="Afficher la confirmation">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <small id="match-text" aria-live="polite"></small>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input @error('terms') is-invalid @enderror"
                           type="checkbox" name="terms" id="terms" required>
                    <label class="form-check-label small" for="terms">
                        J'accepte les
                        <a href="{{ route('cgu') }}" target="_blank" class="text-green text-decoration-none fw-semibold">conditions d'utilisation</a>
                        et la
                        <a href="{{ route('privacy') }}" target="_blank" class="text-green text-decoration-none fw-semibold">politique de confidentialité</a>
                    </label>
                    @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-user-plus me-2"></i>Créer mon compte
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100">
                <i class="fab fa-google me-2"></i>S'inscrire avec Google
            </a>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">
            Déjà un compte ?
            <a href="{{ route('auth.login') }}" class="text-green fw-semibold text-decoration-none">Se connecter</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').classList.toggle('fa-eye');
    btn.querySelector('i').classList.toggle('fa-eye-slash');
}

document.getElementById('password').addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const colors = ['', '#dc3545', '#fd7e14', '#ffc107', '#198754'];
    const labels = ['', 'Faible', 'Moyen', 'Bon', 'Excellent'];
    const widths = ['0%', '25%', '50%', '75%', '100%'];

    const bar = document.getElementById('strength-bar');
    bar.style.width = widths[score];
    bar.style.background = colors[score];
    document.getElementById('strength-text').textContent = labels[score];
    checkMatch();
});

document.getElementById('password_confirmation').addEventListener('input', checkMatch);

function checkMatch() {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('password_confirmation').value;
    const el = document.getElementById('match-text');
    if (!p2) { el.textContent = ''; return; }
    el.textContent = p1 === p2 ? '✓ Les mots de passe correspondent' : '✗ Ne correspondent pas';
    el.style.color = p1 === p2 ? '#198754' : '#dc3545';
}
</script>
</body>
</html>
