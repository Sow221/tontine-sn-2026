<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — Gérez vos tontines en toute sécurité</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green:      #009639;
            --green-dark: #006B2E;
            --yellow:     #FCD116;
            --indigo:     #2D2F53;
            --off-white:  #FDFBF7;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--off-white); color: var(--indigo); overflow-x: hidden; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(160deg, var(--green) 0%, var(--green-dark) 100%);
            min-height: 100vh;
            display: flex; flex-direction: column;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute; top: -80px; right: -80px;
            width: 400px; height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute; bottom: -120px; left: -60px;
            width: 300px; height: 300px;
            background: rgba(252,209,22,0.1);
            border-radius: 50%;
        }
        .hero-nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 24px; position: relative; z-index: 2;
        }
        .hero-logo { font-family: 'Poppins', sans-serif; font-weight: 900; font-size: 1.4rem; color: white; text-decoration: none; }
        .hero-logo span { color: var(--yellow); }
        .hero-body {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; padding: 40px 24px 60px;
            position: relative; z-index: 2;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.15); color: white;
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 999px; padding: 6px 16px; font-size: 13px; font-weight: 500;
            margin-bottom: 24px;
        }
        .hero-title {
            font-family: 'Poppins', sans-serif; font-weight: 900;
            font-size: clamp(2rem, 8vw, 3.2rem); color: white;
            line-height: 1.1; margin-bottom: 16px;
        }
        .hero-title span { color: var(--yellow); }
        .hero-subtitle { color: rgba(255,255,255,0.8); font-size: 1rem; max-width: 340px; margin: 0 auto 36px; line-height: 1.6; }
        .hero-cta {
            display: inline-flex; align-items: center; gap: 10px;
            background: white; color: var(--green);
            font-weight: 700; font-size: 1rem;
            padding: 16px 32px; border-radius: 999px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .hero-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,0.25); color: var(--green-dark); }
        .hero-cta-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,0.8); font-size: 14px; font-weight: 500;
            text-decoration: none; margin-top: 16px;
            transition: color 0.2s;
        }
        .hero-cta-secondary:hover { color: white; }

        /* ── Stats ── */
        .stats-bar {
            background: white;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            border-radius: 20px;
            margin: -32px 16px 0;
            position: relative; z-index: 10;
            padding: 24px 16px;
        }
        .stat-item { text-align: center; }
        .stat-num { font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.6rem; color: var(--green); }
        .stat-lbl { font-size: 12px; color: #94A3B8; font-weight: 500; margin-top: 2px; }

        /* ── Section ── */
        section { padding: 60px 24px; }
        .section-tag {
            display: inline-block; background: #E8F5E9; color: var(--green);
            font-size: 12px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; padding: 4px 14px; border-radius: 999px; margin-bottom: 12px;
        }
        .section-title { font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.7rem; color: var(--indigo); line-height: 1.2; margin-bottom: 12px; }
        .section-sub { color: #64748B; font-size: 15px; line-height: 1.6; }

        /* ── Features ── */
        .feature-card {
            background: white; border-radius: 20px;
            padding: 24px; margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            display: flex; gap: 16px; align-items: flex-start;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .feature-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .feature-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .feature-title { font-weight: 700; font-size: 15px; color: var(--indigo); margin-bottom: 4px; }
        .feature-desc  { font-size: 13px; color: #64748B; line-height: 1.5; }

        /* ── How it works ── */
        .how-bg { background: linear-gradient(160deg, var(--green) 0%, var(--green-dark) 100%); border-radius: 24px; padding: 40px 24px; }
        .step { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 28px; }
        .step:last-child { margin-bottom: 0; }
        .step-num {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(255,255,255,0.2); color: white;
            font-weight: 800; font-size: 16px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .step-title { font-weight: 700; color: white; font-size: 15px; margin-bottom: 4px; }
        .step-desc  { color: rgba(255,255,255,0.75); font-size: 13px; line-height: 1.5; }

        /* ── Types de tontines ── */
        .type-card {
            background: white; border-radius: 16px; padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            text-align: center; height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .type-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .type-emoji { font-size: 32px; margin-bottom: 10px; }
        .type-name  { font-weight: 700; font-size: 14px; color: var(--indigo); margin-bottom: 6px; }
        .type-desc  { font-size: 12px; color: #94A3B8; line-height: 1.4; }

        /* ── Score ── */
        .score-section { background: #F8FAFC; }
        .badge-card {
            background: white; border-radius: 16px; padding: 20px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 12px;
        }
        .badge-icon { font-size: 32px; }
        .badge-name  { font-weight: 700; font-size: 15px; color: var(--indigo); }
        .badge-score { font-size: 13px; color: #94A3B8; }
        .badge-bar   { height: 6px; border-radius: 999px; background: #E2E8F0; margin-top: 8px; overflow: hidden; }
        .badge-fill  { height: 100%; border-radius: 999px; }

        /* ── Testimonials ── */
        .testimonial {
            background: white; border-radius: 20px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 16px;
        }
        .testimonial-text { font-size: 14px; color: #475569; line-height: 1.6; font-style: italic; margin-bottom: 16px; }
        .testimonial-author { display: flex; align-items: center; gap: 12px; }
        .author-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
        }
        .author-name { font-weight: 600; font-size: 14px; color: var(--indigo); }
        .author-role { font-size: 12px; color: #94A3B8; }

        /* ── CTA final ── */
        .cta-section {
            background: linear-gradient(160deg, var(--green) 0%, var(--green-dark) 100%);
            text-align: center; padding: 60px 24px;
        }
        .cta-section h2 { font-family: 'Poppins', sans-serif; font-weight: 900; font-size: 1.8rem; color: white; margin-bottom: 12px; }
        .cta-section p  { color: rgba(255,255,255,0.8); font-size: 15px; margin-bottom: 32px; }
        .cta-btn {
            display: inline-flex; align-items: center; gap: 10px;
            background: white; color: var(--green); font-weight: 700; font-size: 1rem;
            padding: 16px 36px; border-radius: 999px; text-decoration: none;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .cta-btn:hover { transform: translateY(-2px); color: var(--green-dark); }

        /* ── Footer ── */
        footer { background: var(--indigo); color: rgba(255,255,255,0.6); text-align: center; padding: 24px; font-size: 13px; }
        footer a { color: rgba(255,255,255,0.8); text-decoration: none; }
        footer strong { color: white; }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ --}}
<section class="hero">
    <nav class="hero-nav">
        <a href="#" class="hero-logo">🌿 Tontine<span>SN</span></a>
        <a href="{{ route('auth.login') }}" style="color:white; font-weight:600; font-size:14px; text-decoration:none; background:rgba(255,255,255,0.15); padding:8px 18px; border-radius:999px; border:1px solid rgba(255,255,255,0.25);">
            Se connecter
        </a>
    </nav>

    <div class="hero-body">
        <div class="hero-badge">
            <span>🇸🇳</span> Fait pour le Sénégal
        </div>
        <h1 class="hero-title">
            Vos tontines,<br><span>digitalisées</span>
        </h1>
        <p class="hero-subtitle">
            Gérez vos cotisations, suivez vos cycles et recevez vos fonds en toute transparence. Sans papier, sans stress.
        </p>
        <a href="{{ route('auth.login') }}" class="hero-cta">
            <i class="fas fa-arrow-right"></i> Commencer gratuitement
        </a>
        <a href="#comment-ca-marche" class="hero-cta-secondary">
            <i class="fas fa-play-circle"></i> Comment ça marche ?
        </a>
    </div>
</section>

{{-- ══════════════════════════════════════════
     STATS
══════════════════════════════════════════ --}}
<div class="container-fluid px-0">
    <div class="stats-bar">
        <div class="row g-0">
            <div class="col-4 stat-item">
                <div class="stat-num">4</div>
                <div class="stat-lbl">Types de tontines</div>
            </div>
            <div class="col-4 stat-item" style="border-left:1px solid #E2E8F0; border-right:1px solid #E2E8F0;">
                <div class="stat-num">50</div>
                <div class="stat-lbl">Membres max</div>
            </div>
            <div class="col-4 stat-item">
                <div class="stat-num">100%</div>
                <div class="stat-lbl">Gratuit</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     FONCTIONNALITÉS
══════════════════════════════════════════ --}}
<section>
    <div class="section-tag">Fonctionnalités</div>
    <h2 class="section-title">Tout ce dont vous avez besoin</h2>
    <p class="section-sub mb-4">Une plateforme complète pensée pour les réalités locales.</p>

    <div class="feature-card">
        <div class="feature-icon" style="background:#E8F5E9;">💰</div>
        <div>
            <div class="feature-title">Gestion des cotisations</div>
            <div class="feature-desc">Suivez chaque paiement en temps réel. Wave, Orange Money ou espèces — tout est enregistré.</div>
        </div>
    </div>

    <div class="feature-card">
        <div class="feature-icon" style="background:#FFF8E1;">🎯</div>
        <div>
            <div class="feature-title">Tirage au sort équitable</div>
            <div class="feature-desc">Tirage séquentiel ou aléatoire, visible par tous les membres. Zéro contestation possible.</div>
        </div>
    </div>

    <div class="feature-card">
        <div class="feature-icon" style="background:#EDE7F6;">⭐</div>
        <div>
            <div class="feature-title">Score de crédit</div>
            <div class="feature-desc">Chaque membre accumule un score basé sur sa ponctualité. Bronze, Argent ou Or — valorisez votre fiabilité.</div>
        </div>
    </div>

    <div class="feature-card">
        <div class="feature-icon" style="background:#E3F2FD;">🔔</div>
        <div>
            <div class="feature-title">Rappels automatiques</div>
            <div class="feature-desc">Notifications push avant chaque échéance. Plus personne n'oublie de payer.</div>
        </div>
    </div>

    <div class="feature-card">
        <div class="feature-icon" style="background:#FCE4EC;">🔐</div>
        <div>
            <div class="feature-title">Connexion sécurisée</div>
            <div class="feature-desc">Lien magique par email. Pas de mot de passe à retenir, connexion en un clic.</div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     COMMENT ÇA MARCHE
══════════════════════════════════════════ --}}
<section id="comment-ca-marche" style="padding-top:0;">
    <div class="how-bg">
        <div class="section-tag" style="background:rgba(255,255,255,0.2); color:white;">Comment ça marche</div>
        <h2 class="section-title" style="color:white;" class="mb-4">En 4 étapes simples</h2>

        <div class="step">
            <div class="step-num">1</div>
            <div>
                <div class="step-title">Créez votre tontine</div>
                <div class="step-desc">Choisissez le type, le montant, la fréquence et invitez vos membres.</div>
            </div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div>
                <div class="step-title">Les membres rejoignent</div>
                <div class="step-desc">Chaque membre s'inscrit avec son email et rejoint la tontine via un code.</div>
            </div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div>
                <div class="step-title">Payez vos cotisations</div>
                <div class="step-desc">Wave, Orange Money ou espèces — chaque paiement est enregistré et visible par tous.</div>
            </div>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <div>
                <div class="step-title">Recevez votre tour</div>
                <div class="step-desc">Le tirage désigne le bénéficiaire. Le montant collecté lui est remis automatiquement.</div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     TYPES DE TONTINES
══════════════════════════════════════════ --}}
<section>
    <div class="section-tag">Flexibilité</div>
    <h2 class="section-title">4 types de tontines</h2>
    <p class="section-sub mb-4">Adaptez la tontine à votre groupe et vos besoins.</p>

    <div class="row g-3">
        <div class="col-6">
            <div class="type-card">
                <div class="type-emoji">🔄</div>
                <div class="type-name">Fixe</div>
                <div class="type-desc">Montant identique pour tous, chaque cycle.</div>
            </div>
        </div>
        <div class="col-6">
            <div class="type-card">
                <div class="type-emoji">🏷️</div>
                <div class="type-name">Enchère</div>
                <div class="type-desc">Les membres enchérissent pour obtenir le tour.</div>
            </div>
        </div>
        <div class="col-6">
            <div class="type-card">
                <div class="type-emoji">🏦</div>
                <div class="type-name">Épargne forcée</div>
                <div class="type-desc">Accumulation progressive sans tirage immédiat.</div>
            </div>
        </div>
        <div class="col-6">
            <div class="type-card">
                <div class="type-emoji">🎉</div>
                <div class="type-name">Cérémonielle</div>
                <div class="type-desc">Liée à un événement : baptême, mariage, Tabaski.</div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     SCORE DE CRÉDIT
══════════════════════════════════════════ --}}
<section class="score-section">
    <div class="section-tag">Score de crédit</div>
    <h2 class="section-title">Valorisez votre fiabilité</h2>
    <p class="section-sub mb-4">Chaque paiement à temps améliore votre score et débloque des badges.</p>

    <div class="badge-card">
        <div class="badge-icon">🥇</div>
        <div style="flex:1;">
            <div class="badge-name">Badge Or</div>
            <div class="badge-score">Score ≥ 8.5 / 10</div>
            <div class="badge-bar"><div class="badge-fill" style="width:90%; background:#FCD116;"></div></div>
        </div>
    </div>
    <div class="badge-card">
        <div class="badge-icon">🥈</div>
        <div style="flex:1;">
            <div class="badge-name">Badge Argent</div>
            <div class="badge-score">Score ≥ 6.5 / 10</div>
            <div class="badge-bar"><div class="badge-fill" style="width:65%; background:#94A3B8;"></div></div>
        </div>
    </div>
    <div class="badge-card">
        <div class="badge-icon">🥉</div>
        <div style="flex:1;">
            <div class="badge-name">Badge Bronze</div>
            <div class="badge-score">Score ≥ 4.0 / 10</div>
            <div class="badge-bar"><div class="badge-fill" style="width:40%; background:#CD7F32;"></div></div>
        </div>
    </div>

    <p class="mt-3" style="font-size:13px; color:#94A3B8; text-align:center;">
        Score calculé sur : ponctualité (50%) · montant contribué (30%) · ancienneté (20%)
    </p>
</section>

{{-- ══════════════════════════════════════════
     TÉMOIGNAGES
══════════════════════════════════════════ --}}
<section>
    <div class="section-tag">Témoignages</div>
    <h2 class="section-title">Ils nous font confiance</h2>

    <div class="testimonial">
        <div class="testimonial-text">
            "Avant on gérait tout sur un cahier. Maintenant tout le monde voit les paiements en temps réel. Plus de disputes, plus de doutes."
        </div>
        <div class="testimonial-author">
            <div class="author-avatar">FD</div>
            <div>
                <div class="author-name">Fatou Diallo</div>
                <div class="author-role">Gérante de tontine, Dakar</div>
            </div>
        </div>
    </div>

    <div class="testimonial">
        <div class="testimonial-text">
            "Le lien de connexion par email c'est simple et rapide. Pas besoin de retenir un mot de passe. J'adore."
        </div>
        <div class="testimonial-author">
            <div class="author-avatar">AS</div>
            <div>
                <div class="author-name">Aminata Sow</div>
                <div class="author-role">Membre, Thiès</div>
            </div>
        </div>
    </div>

    <div class="testimonial">
        <div class="testimonial-text">
            "Mon score Or me donne de la crédibilité dans mon groupe. Les autres membres me font plus confiance maintenant."
        </div>
        <div class="testimonial-author">
            <div class="author-avatar">MN</div>
            <div>
                <div class="author-name">Moussa Ndiaye</div>
                <div class="author-role">Membre, Saint-Louis</div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════
     CTA FINAL
══════════════════════════════════════════ --}}
<section class="cta-section">
    <h2>Prêt à digitaliser<br>votre tontine ?</h2>
    <p>Rejoignez TontineSN gratuitement.<br>Aucune carte bancaire requise.</p>
    <a href="{{ route('auth.login') }}" class="cta-btn">
        <i class="fas fa-arrow-right"></i> Créer mon compte
    </a>
</section>

{{-- ══════════════════════════════════════════
     FOOTER
══════════════════════════════════════════ --}}
<footer>
    <p class="mb-1"><strong>🌿 TontineSN</strong></p>
    <p>Gérez vos tontines en toute sécurité · Sénégal 🇸🇳</p>
    <p class="mt-2">
        <a href="{{ route('auth.login') }}">Se connecter</a>
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
