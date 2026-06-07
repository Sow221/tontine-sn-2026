<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Invitation TontineSN</title>
    <meta property="og:site_name" content="TontineSN">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $excerpt }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $excerpt }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Inter, system-ui, Arial, sans-serif; background: #f7fafc; color: #111827; }
        .invite-card { max-width: 900px; margin: 40px auto; padding: 32px; background: white; border-radius: 20px; box-shadow: 0 22px 60px rgba(15, 23, 42, .08); }
        .invite-badge { display: inline-flex; gap: .5rem; align-items: center; padding: .55rem 1rem; border-radius: 9999px; background: #ecfdf5; color: #065f46; font-weight: 700; }
        .invite-link { word-break: break-all; }
        .invite-cta { display: inline-flex; align-items: center; gap: .75rem; }
    </style>
</head>
<body>
    <div class="invite-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('home') }}" style="font-weight:700;color:#009639;text-decoration:none;font-size:1.1rem;">TontineSN</a>
            @auth
            <a href="{{ route('tontines.join.form', ['code' => $tontine->code]) }}" class="btn btn-success rounded-pill">Rejoindre</a>
            @else
            <div class="d-flex gap-2">
                <a href="{{ route('auth.login') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Se connecter</a>
                <a href="{{ route('auth.register') }}" class="btn btn-success btn-sm rounded-pill">S'inscrire</a>
            </div>
            @endauth
        </div>
        <div class="d-flex justify-content-between align-items-start mb-4 flex-column flex-md-row gap-3">
            <div>
                <span class="invite-badge">Invitation Tontine</span>
                <h1 class="mt-3 mb-2">{{ $tontine->name }}</h1>
                <p class="text-muted fs-5">{{ $excerpt }}</p>
            </div>
            <div class="text-end">
                <p class="mb-1 text-muted">Code d’invitation</p>
                <div class="fs-3 fw-semibold">{{ $tontine->code }}</div>
            </div>
        </div>

        <div class="row gx-4 gy-3">
            <div class="col-lg-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Montant</dt>
                    <dd class="col-7">{{ number_format($tontine->amount, 0, ',', ' ') }} FCFA</dd>
                    <dt class="col-5 text-muted">Fréquence</dt>
                    <dd class="col-7">{{ ucfirst($tontine->frequency) }}</dd>
                    <dt class="col-5 text-muted">Membres max</dt>
                    <dd class="col-7">{{ $tontine->max_members }}</dd>
                </dl>
            </div>
            <div class="col-lg-6">
                <div class="p-3 rounded-4" style="background:#f0fdf4;">
                    <p class="mb-2 text-muted">Lien pour rejoindre</p>
                    <p class="invite-link mb-2">{{ $inviteUrl }}</p>
                    <a href="{{ $inviteUrl }}" class="btn btn-success invite-cta">Rejoindre maintenant</a>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <p class="text-muted">Partagez cette page sur WhatsApp, Facebook ou LinkedIn pour inviter d’autres membres à votre tontine.</p>
        </div>
    </div>
</body>
</html>
