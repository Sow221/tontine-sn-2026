<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TontineSN — La tontine numérique du Sénégal</title>
    <meta name="description" content="Créez et gérez vos tontines en ligne. Paiement Wave & Orange Money. Accessible via smartphone ou *144#. En français et en Wolof.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
    <link href="{{ asset('css/landing.css') }}" rel="stylesheet">
</head>
<body class="landing-body">

{{-- ═══════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════ --}}
<nav class="landing-nav" id="landingNav">
    <div class="landing-nav-inner">
        <a href="#" class="landing-logo">
            <span class="logo-icon">🌿</span>
            <span class="logo-text">TontineSN</span>
        </a>
        <div class="landing-nav-links">
            <a href="#comment">Comment ça marche</a>
            <a href="#pourquoi">Pourquoi nous</a>
            <a href="#ussd">Accès USSD</a>
        </div>
        <div class="landing-nav-actions">
            <a href="?lang=wo" class="lang-btn">🇸🇳 Wolof</a>
            <a href="{{ route('auth.login') }}" class="btn-nav-login">Se connecter</a>
            <a href="{{ route('auth.login') }}" class="btn-nav-cta">Commencer</a>
        </div>
        <button class="landing-burger" id="burgerBtn" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
    {{-- Mobile menu --}}
    <div class="landing-mobile-menu" id="mobileMenu">
        <a href="#comment">Comment ça marche</a>
        <a href="#pourquoi">Pourquoi nous</a>
        <a href="#ussd">Accès USSD</a>
        <hr>
        <a href="{{ route('auth.login') }}" class="btn btn-primary w-100 mt-2">Commencer gratuitement</a>
    </div>
</nav>

{{-- ═══════════════════════════════════════════
     HERO
═══════════════════════════════════════════ --}}
<section class="hero-section">
    <div class="hero-bg-pattern"></div>
    <div class="container">
        <div class="row align-items-center min-vh-hero">
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <span>🇸🇳</span> Fait au Sénégal, pour le Sénégal
                </div>
                <h1 class="hero-title">
                    Votre tontine,<br>
                    <span class="hero-title-accent">enfin numérique.</span>
                </h1>
                <p class="hero-subtitle">
                    TontineSN vous permet de créer, gérer et cotiser dans vos tontines —
                    via smartphone ou simple téléphone, en français ou en Wolof.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('auth.login') }}" class="btn-hero-primary">
                        <i class="fas fa-arrow-right me-2"></i>Rejoindre gratuitement
                    </a>
                    <a href="#comment" class="btn-hero-secondary">
                        Voir comment ça marche <i class="fas fa-chevron-down ms-1"></i>
                    </a>
                </div>
                <div class="hero-trust">
                    <span><i class="fas fa-check-circle text-green me-1"></i>Sans mot de passe</span>
                    <span><i class="fas fa-check-circle text-green me-1"></i>Wave & Orange Money</span>
                    <span><i class="fas fa-check-circle text-green me-1"></i>Accessible via *144#</span>
                </div>
            </div>
            <div class="col-lg-6 hero-visual d-none d-lg-flex">
                <div class="hero-mockup">
                    <div class="mockup-phone">
                        <div class="mockup-screen">
                            <div class="mockup-header">
                                <span class="mockup-logo">🌿 TontineSN</span>
                                <span class="mockup-badge-score">Score 8.2 🥇</span>
                            </div>
                            <div class="mockup-card green">
                                <div class="mockup-card-label">Tontine Famille Diallo</div>
                                <div class="mockup-card-amount">25 000 <small>FCFA / mois</small></div>
                                <div class="mockup-card-meta">
                                    <span>👥 8 membres</span>
                                    <span class="mockup-badge-active">Actif</span>
                                </div>
                            </div>
                            <div class="mockup-card yellow">
                                <div class="mockup-card-label">Tontine Amis Thiès</div>
                                <div class="mockup-card-amount">10 000 <small>FCFA / mois</small></div>
                                <div class="mockup-card-meta">
                                    <span>👥 5 membres</span>
                                    <span class="mockup-badge-turn">Votre tour !</span>
                                </div>
                            </div>
                            <div class="mockup-pay-btn">
                                <i class="fas fa-bolt me-2"></i>Payer ma cotisation
                            </div>
                            <div class="mockup-payment-row">
                                <div class="mockup-pay-wave">Wave</div>
                                <div class="mockup-pay-om">Orange Money</div>
                            </div>
                        </div>
                    </div>
                    <div class="mockup-floating mockup-float-1">
                        ✅ Cotisation reçue
                    </div>
                    <div class="mockup-floating mockup-float-2">
                        🔔 Votre tour ce mois !
                    </div>
                    <div class="mockup-floating mockup-float-3">
                        📊 Score : Or 🥇
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="#FDFBF7"/>
        </svg>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     PROBLÈME / EMPATHIE
═══════════════════════════════════════════ --}}
<section class="problem-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <h2 class="section-title">
                    La tontine, ça marche depuis toujours.<br>
                    <span class="text-muted fw-normal">Le carnet papier, beaucoup moins.</span>
                </h2>
                <p class="section-subtitle">
                    Oublis de paiement. Carnets perdus. Conflits sur l'ordre des tours.
                    Membres absents le jour de la collecte.
                </p>
            </div>
        </div>
        <div class="row g-3 mt-2 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="problem-card">
                    <div class="problem-icon">📓</div>
                    <p>Carnets papier perdus ou abîmés</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="problem-card">
                    <div class="problem-icon">😤</div>
                    <p>Conflits sur l'ordre des tours</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="problem-card">
                    <div class="problem-icon">🏃</div>
                    <p>Membres absents à la collecte</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="problem-card">
                    <div class="problem-icon">🤷</div>
                    <p>Aucune traçabilité des paiements</p>
                </div>
            </div>
        </div>
        <div class="problem-solution-bridge">
            <div class="bridge-line"></div>
            <div class="bridge-text">TontineSN résout tout ça — sans changer ce qui fait la force de votre tontine.</div>
            <div class="bridge-line"></div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     COMMENT ÇA MARCHE
═══════════════════════════════════════════ --}}
<section class="how-section" id="comment">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-badge">Comment ça marche</div>
            <h2 class="section-title">Simple comme bonjour.</h2>
            <p class="section-subtitle">3 étapes, et votre tontine tourne toute seule.</p>
        </div>
        <div class="row g-4 align-items-start">
            <div class="col-md-4">
                <div class="how-step">
                    <div class="how-step-number">01</div>
                    <div class="how-step-icon">🤝</div>
                    <h3 class="how-step-title">Créez ou rejoignez une tontine</h3>
                    <p class="how-step-desc">
                        Invitez vos membres par lien ou code.
                        Définissez le montant, la fréquence et l'ordre des tours.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="how-step how-step-featured">
                    <div class="how-step-number">02</div>
                    <div class="how-step-icon">💸</div>
                    <h3 class="how-step-title">Cotisez en un tap</h3>
                    <p class="how-step-desc">
                        Payez directement via Wave ou Orange Money.
                        Chaque paiement est enregistré et visible par tous les membres.
                    </p>
                    <div class="how-step-payments">
                        <span class="pay-chip wave">Wave</span>
                        <span class="pay-chip om">Orange Money</span>
                        <span class="pay-chip ussd">*144#</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="how-step">
                    <div class="how-step-number">03</div>
                    <div class="how-step-icon">🎯</div>
                    <h3 class="how-step-title">Recevez votre tour automatiquement</h3>
                    <p class="how-step-desc">
                        Le système gère l'ordre, les rappels et les versements.
                        Vous n'avez plus rien à coordonner manuellement.
                    </p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('auth.login') }}" class="btn-section-cta">
                Créer ma première tontine <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     DIFFÉRENCIATEURS
═══════════════════════════════════════════ --}}
<section class="features-section" id="pourquoi">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-badge">Pourquoi TontineSN</div>
            <h2 class="section-title">Fait pour tout le monde.<br><span class="text-green">Vraiment.</span></h2>
            <p class="section-subtitle">
                Pas une app de plus. Une solution pensée pour la réalité sénégalaise.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:#E8F5E9">
                        <span>📱</span>
                    </div>
                    <h4 class="feature-title">Smartphone ou téléphone basique</h4>
                    <p class="feature-desc">
                        Accédez à votre tontine via l'app ou composez simplement <strong>*144#</strong> depuis n'importe quel téléphone.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card feature-card-highlight">
                    <div class="feature-icon-wrap" style="background:rgba(255,255,255,0.2)">
                        <span>🇫🇷🇸🇳</span>
                    </div>
                    <h4 class="feature-title">Français et Wolof</h4>
                    <p class="feature-desc">
                        L'interface parle votre langue. Pas une traduction approximative — écrit par et pour des Sénégalais.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:#FFF8E1">
                        <span>📊</span>
                    </div>
                    <h4 class="feature-title">Score de crédit communautaire</h4>
                    <p class="feature-desc">
                        Chaque membre construit sa réputation de paiement. Un gage de confiance pour toute la communauté.
                    </p>
                    <div class="feature-score-badges">
                        <span class="score-badge bronze">🥉 Bronze</span>
                        <span class="score-badge silver">🥈 Argent</span>
                        <span class="score-badge gold">🥇 Or</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon-wrap" style="background:#FFEBEE">
                        <span>🔒</span>
                    </div>
                    <h4 class="feature-title">Sécurité sans complexité</h4>
                    <p class="feature-desc">
                        Connexion par code OTP — aucun mot de passe à retenir. Vos paiements sont sécurisés et traçables.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     CONFIANCE
═══════════════════════════════════════════ --}}
<section class="trust-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="section-badge">Transparence totale</div>
                <h2 class="section-title">
                    La technologie au service<br>de la confiance.
                    <span class="text-muted fw-normal"> Pas l'inverse.</span>
                </h2>
                <p class="trust-text">
                    Une tontine repose sur une chose : <strong>la confiance entre membres</strong>.
                    TontineSN ne remplace pas cette confiance — elle la renforce.
                </p>
                <div class="trust-list">
                    <div class="trust-item">
                        <div class="trust-item-icon">✅</div>
                        <div>
                            <strong>Chaque cotisation est enregistrée</strong>
                            <p>Visible par tous les membres en temps réel.</p>
                        </div>
                    </div>
                    <div class="trust-item">
                        <div class="trust-item-icon">🎯</div>
                        <div>
                            <strong>Chaque tour est transparent</strong>
                            <p>L'ordre est décidé collectivement et affiché pour tous.</p>
                        </div>
                    </div>
                    <div class="trust-item">
                        <div class="trust-item-icon">📋</div>
                        <div>
                            <strong>Historique complet consultable</strong>
                            <p>Chaque membre a un historique de paiement vérifiable.</p>
                        </div>
                    </div>
                </div>
                <div class="trust-quote">
                    Plus de "il a dit / elle a dit".<br>
                    <strong>Les faits parlent d'eux-mêmes.</strong>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="trust-visual">
                    <div class="trust-stat-grid">
                        <div class="trust-stat">
                            <div class="trust-stat-value text-green">100%</div>
                            <div class="trust-stat-label">Transactions traçables</div>
                        </div>
                        <div class="trust-stat">
                            <div class="trust-stat-value text-green">0</div>
                            <div class="trust-stat-label">Mot de passe requis</div>
                        </div>
                        <div class="trust-stat">
                            <div class="trust-stat-value text-green">3</div>
                            <div class="trust-stat-label">Langues supportées</div>
                        </div>
                        <div class="trust-stat">
                            <div class="trust-stat-value text-green">2</div>
                            <div class="trust-stat-label">Moyens de paiement</div>
                        </div>
                    </div>
                    <div class="trust-security-badges">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt text-green me-2"></i>
                            OTP sécurisé · 5 min TTL
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-lock text-green me-2"></i>
                            Webhooks HMAC-SHA256
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-history text-green me-2"></i>
                            Journalisation complète
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     USSD
═══════════════════════════════════════════ --}}
<section class="ussd-section" id="ussd">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 text-center">
                <div class="ussd-phone">
                    <div class="ussd-screen">
                        <div class="ussd-screen-header">TontineSN *144#</div>
                        <div class="ussd-menu-item"><span>1.</span> Mes tontines</div>
                        <div class="ussd-menu-item"><span>2.</span> Payer cotisation</div>
                        <div class="ussd-menu-item"><span>3.</span> Voir bénéficiaires</div>
                        <div class="ussd-menu-item"><span>4.</span> Historique</div>
                        <div class="ussd-menu-item"><span>5.</span> Mon score crédit</div>
                        <div class="ussd-menu-item"><span>0.</span> Changer langue</div>
                        <div class="ussd-input-row">
                            <span>Choix :</span>
                            <span class="ussd-cursor">_</span>
                        </div>
                    </div>
                    <div class="ussd-keypad">
                        <div class="ussd-key">1</div><div class="ussd-key">2</div><div class="ussd-key">3</div>
                        <div class="ussd-key">4</div><div class="ussd-key">5</div><div class="ussd-key">6</div>
                        <div class="ussd-key">7</div><div class="ussd-key">8</div><div class="ussd-key">9</div>
                        <div class="ussd-key">*</div><div class="ussd-key ussd-key-dial">144#</div><div class="ussd-key">#</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="section-badge">Accès universel</div>
                <h2 class="section-title">
                    Pas de smartphone ?<br>
                    <span class="text-green">Aucun problème.</span>
                </h2>
                <p class="ussd-text">
                    TontineSN est la seule app de tontine accessible depuis
                    <strong>n'importe quel téléphone au Sénégal</strong>.
                </p>
                <div class="ussd-features">
                    <div class="ussd-feature-item">
                        <span class="ussd-feature-icon">📱</span>
                        <span>Composez <strong>*144#</strong> depuis votre téléphone</span>
                    </div>
                    <div class="ussd-feature-item">
                        <span class="ussd-feature-icon">🌍</span>
                        <span>Fonctionne partout au Sénégal, même sans internet</span>
                    </div>
                    <div class="ussd-feature-item">
                        <span class="ussd-feature-icon">🇸🇳</span>
                        <span>Interface disponible en Wolof</span>
                    </div>
                    <div class="ussd-feature-item">
                        <span class="ussd-feature-icon">⚡</span>
                        <span>Payez votre cotisation en quelques secondes</span>
                    </div>
                </div>
                <div class="ussd-quote">
                    Parce que la solidarité ne devrait pas dépendre<br>
                    <strong>du modèle de votre téléphone.</strong>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     CTA FINAL
═══════════════════════════════════════════ --}}
<section class="cta-section">
    <div class="cta-bg-pattern"></div>
    <div class="container text-center">
        <div class="cta-inner">
            <div class="cta-emoji">🌿</div>
            <h2 class="cta-title">Votre communauté vous attend.</h2>
            <p class="cta-subtitle">
                Créez votre première tontine en moins de 2 minutes.
            </p>
            <a href="{{ route('auth.login') }}" class="btn-cta-main">
                <i class="fas fa-arrow-right me-2"></i>Commencer maintenant — c'est gratuit
            </a>
            <div class="cta-reassurance">
                <span><i class="fas fa-check me-1"></i>Aucune carte bancaire requise</span>
                <span><i class="fas fa-check me-1"></i>Connexion sécurisée par OTP</span>
                <span><i class="fas fa-check me-1"></i>Disponible en français et en Wolof</span>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════ --}}
<footer class="landing-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <span class="logo-icon">🌿</span>
                <span class="logo-text">TontineSN</span>
                <p class="footer-tagline">La tontine numérique du Sénégal.</p>
            </div>
            <div class="footer-langs">
                <a href="?lang=fr" class="footer-lang-btn active">🇫🇷 Français</a>
                <a href="?lang=wo" class="footer-lang-btn">🇸🇳 Wolof</a>
                <a href="?lang=en" class="footer-lang-btn">🇬🇧 English</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2025 TontineSN — Fait avec ❤️ au Sénégal</span>
            <div class="footer-links">
                <a href="#">Contact</a>
                <a href="#">À propos</a>
                <a href="#">Confidentialité</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar scroll effect
const nav = document.getElementById('landingNav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
});

// Burger menu
document.getElementById('burgerBtn').addEventListener('click', () => {
    document.getElementById('mobileMenu').classList.toggle('open');
    document.getElementById('burgerBtn').classList.toggle('open');
});

// Close mobile menu on link click
document.querySelectorAll('#mobileMenu a').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.remove('open');
        document.getElementById('burgerBtn').classList.remove('open');
    });
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('revealed');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.how-step, .feature-card, .trust-item, .problem-card, .trust-stat').forEach(el => {
    el.classList.add('reveal');
    observer.observe(el);
});

// USSD cursor blink
setInterval(() => {
    const cursor = document.querySelector('.ussd-cursor');
    if (cursor) cursor.style.opacity = cursor.style.opacity === '0' ? '1' : '0';
}, 600);
</script>
</body>
</html>
