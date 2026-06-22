<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#009639">
<meta name="description" content="Gérez votre tontine en ligne au Sénégal. Cotisations Wave & Orange Money, score de crédit communautaire, 4 types de tontines. Gratuit | TontineSN">
<meta property="og:title" content="Tontine en Ligne - Épargne &amp; Cotisations Sénégal | TontineSN">
<meta property="og:description" content="4 types de tontines. Score de crédit communautaire. Wave & Orange Money intégrés. La tontine traditionnelle, enfin numérique.">
<meta property="og:image" content="{{ asset('images/hero-community.webp') }}">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:type" content="website">
<link href="{{ asset('css/vendor/google-fonts.css') }}" rel="stylesheet">
<link rel="canonical" href="{{ url('/') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-192.svg') }}">
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/icon-192.png') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="TontineSN">
<link href="{{ asset('css/landing.css') }}" rel="stylesheet">
<title>Tontine en Ligne - Épargne &amp; Cotisations Sénégal | TontineSN</title>
</head>
<body>

<a href="#main-content" class="ls-skip">Aller au contenu principal</a>

<!-- NAV -->
<header role="banner">
<nav class="ls-nav" id="nav" aria-label="Navigation principale">
  <div class="ls-nav__inner">
    <a href="{{ route('home') }}" class="ls-nav__logo" aria-label="TontineSN — Accueil">
      <picture>
        <source srcset="{{ asset('images/element-logo.webp') }}" type="image/webp">
        <img src="{{ asset('images/element-logo.png') }}" alt="Logo TontineSN - Plateforme de gestion de tontine en ligne" class="ls-nav__logo-img" width="38" height="38">
      </picture>
      <span class="ls-nav__logo-text">TontineSN</span>
    </a>
    <div class="ls-nav__links">
      <a href="#comment" class="ls-nav__link">Comment &ccedil;a marche</a>
      <a href="#types" class="ls-nav__link">Nos tontines</a>
      <a href="#pourquoi" class="ls-nav__link">Fonctionnalit&eacute;s</a>
      <a href="#score" class="ls-nav__link">Score</a>
      <a href="#faq-section" class="ls-nav__link">FAQ</a>
    </div>
    <div class="ls-nav__actions">
      <a href="{{ route('auth.login') }}" class="ls-btn-ghost">Se connecter</a>
      <a href="{{ route('auth.register') }}" class="ls-btn-primary-nav">Commencer</a>
    </div>
    <button class="ls-burger" id="burgerBtn" aria-label="Menu" aria-expanded="false">
      <span class="ls-burger__line"></span>
      <span class="ls-burger__line"></span>
      <span class="ls-burger__line"></span>
    </button>
  </div>
  <div class="ls-mobile" id="mobileMenu" role="navigation" aria-label="Menu mobile">
    <a href="#comment">Comment &ccedil;a marche</a>
    <a href="#types">Nos tontines</a>
    <a href="#pourquoi">Fonctionnalit&eacute;s</a>
    <a href="#score">Score</a>
    <a href="#faq-section">FAQ</a>
    <hr style="border:none;border-top:1px solid var(--gray-200);margin:8px 0;">
    <a href="{{ route('auth.register') }}" role="menuitem" style="display:block;background:var(--green);color:white;text-align:center;padding:12px;border-radius:var(--radius-full);font-weight:700;margin-top:8px;">Commencer gratuitement</a>
  </div>
</nav>
</header>

<main id="main-content">

<!-- ════════════════════════════════
  1. HERO
════════════════════════════════ -->
<section class="ls-hero" aria-label="Présentation">
  <div class="ls-hero__bg" aria-hidden="true"></div>
  <div class="ls-hero__star" aria-hidden="true">&#9733;</div>
  <div class="ls-hero__inner">
    <div class="ls-hero__content">
      <div class="ls-hero__badges-row">
        <div class="ls-hero__badge">
          <span class="ls-hero__badge-star" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>
          Fait au S&eacute;n&eacute;gal
        </div>
        <div class="ls-hero__badge ls-hero__badge--fintech">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> Fintech &amp; Innovation sociale
        </div>
      </div>
      <h1 class="ls-hero__title">
        Z&eacute;ro carnet.<br>
        <span class="ls-hero__title-accent">Z&eacute;ro conflit. Z&eacute;ro oubli.</span>
      </h1>
      <p class="ls-hero__slogan">La tontine traditionnelle, enfin num&eacute;rique.</p>
      <p class="ls-hero__desc">
        Pour les <strong>familles</strong>, <strong>associations de quartier</strong> et <strong>groupes professionnels</strong> &mdash;
        TontineSN num&eacute;rise votre tontine sans en changer l&rsquo;esprit.
        Votre groupe cotise, votre r&eacute;putation se construit, votre argent circule en toute s&eacute;curit&eacute;.
      </p>
      <div class="ls-hero__actions">
        <a href="{{ route('auth.register') }}" class="ls-hero__cta">
          Commencer gratuitement
          <span class="ls-hero__cta-arrow" aria-hidden="true">&#8594;</span>
        </a>
        <a href="#comment" class="ls-hero__link">
          Comment &ccedil;a marche
          <span class="ls-hero__link-chevron" aria-hidden="true">&#8595;</span>
        </a>
      </div>
      <div class="ls-hero__trust">
        <span class="ls-hero__trust-item">
          <span class="ls-hero__trust-check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          Toujours gratuit
        </span>
        <span class="ls-hero__trust-item">
          <span class="ls-hero__trust-check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          Wave &amp; Orange Money
        </span>

        <span class="ls-hero__trust-item">
          <span class="ls-hero__trust-check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          4 types de tontines
        </span>
      </div>
    </div>
    <div class="ls-hero__visual" aria-hidden="true">
      <div class="ls-mockup">
        <div class="ls-mockup__phone">
          <div class="ls-mockup__screen">
            <div class="ls-mockup__header">
              <span class="ls-mockup__logo-text">TontineSN</span>
              <span class="ls-mockup__score-badge">Score 8.2 &#9733;</span>
            </div>
            <div class="ls-mockup__card ls-mockup__card--green">
              <div class="ls-mockup__card-label">Tontine Famille Diallo</div>
              <div class="ls-mockup__card-amount">25 000 <small>FCFA / mois</small></div>
              <div class="ls-mockup__card-meta">
                <span>&#x1F465; 8 membres</span>
                <span class="ls-mockup__card-badge ls-mockup__card-badge--active">Actif</span>
              </div>
            </div>
            <div class="ls-mockup__card ls-mockup__card--gold">
              <div class="ls-mockup__card-label">Tontine Amis Thi&egrave;s</div>
              <div class="ls-mockup__card-amount">10 000 <small>FCFA / mois</small></div>
              <div class="ls-mockup__card-meta">
                <span>&#x1F465; 5 membres</span>
                <span class="ls-mockup__card-badge ls-mockup__card-badge--turn">Votre tour !</span>
              </div>
            </div>
            <div class="ls-mockup__pay-btn">&#x26A1; Payer ma cotisation</div>
            <div class="ls-mockup__payments">
              <div class="ls-mockup__pay ls-mockup__pay--wave">Wave</div>
              <div class="ls-mockup__pay ls-mockup__pay--om">Orange Money</div>
            </div>
          </div>
        </div>
        <div class="ls-mockup__float ls-mockup__float--1"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Cotisation re&ccedil;ue</div>
        <div class="ls-mockup__float ls-mockup__float--2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg> Votre tour ce mois !</div>
        <div class="ls-mockup__float ls-mockup__float--3"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> Score : Or <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
      </div>
    </div>
  </div>
  <div class="ls-hero__wave" aria-hidden="true">
    <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="#FDF8F0"/>
    </svg>
  </div>
</section>

<!-- ════════════════════════════════
  2. PROBLEM
════════════════════════════════ -->
<section class="ls-problem" aria-labelledby="problem-title">
  <div class="ls-container">
    <div class="ls-problem__header">
      <h2 id="problem-title" class="ls-section-title">
        Plus de <span class="ls-text-green">2 millions de S&eacute;n&eacute;galais</span> pratiquent la tontine.<br>
        <span style="font-weight:400;color:var(--gray-300);">Presque tous avec un carnet papier.</span>
      </h2>
      <p class="ls-section-desc" style="margin:0 auto;">
        La tontine est le premier m&eacute;canisme d&rsquo;&eacute;pargne collective informelle du pays &mdash;
        mais elle reste fragile, opaque et conflictuelle faute d&rsquo;outils adapt&eacute;s.
      </p>
    </div>
    <div class="ls-problem__grid">
      <div class="ls-problem__card ls-reveal">
        <span class="ls-problem__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg></span>
        <p class="ls-problem__text">Carnets papier perdus ou ab&icirc;m&eacute;s</p>
      </div>
      <div class="ls-problem__card ls-reveal ls-reveal--delay-1">
        <span class="ls-problem__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg></span>
        <p class="ls-problem__text">Conflits sur l&rsquo;ordre des tours</p>
      </div>
      <div class="ls-problem__card ls-reveal ls-reveal--delay-2">
        <span class="ls-problem__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" x2="22" y1="8" y2="13"/><line x1="22" x2="17" y1="8" y2="13"/></svg></span>
        <p class="ls-problem__text">Membres absents &agrave; la collecte</p>
      </div>
      <div class="ls-problem__card ls-reveal ls-reveal--delay-3">
        <span class="ls-problem__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m15 11-6 6"/><path d="m9 11 6 6"/></svg></span>
        <p class="ls-problem__text">Aucune tra&ccedil;abilit&eacute; des paiements</p>
      </div>
    </div>
    <div class="ls-problem__bridge">
      <span class="ls-problem__bridge-line" aria-hidden="true"></span>
      <span class="ls-problem__bridge-text">TontineSN num&eacute;rise la tontine sans trahir son essence &mdash; la confiance entre les membres reste au c&oelig;ur.</span>
      <span class="ls-problem__bridge-line--solid" aria-hidden="true"></span>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
  3. HOW IT WORKS
════════════════════════════════ -->
<section class="ls-how" id="comment" aria-labelledby="how-title">
  <div class="ls-container">
    <div class="ls-how__header">
      <div class="ls-section-badge">Comment &ccedil;a marche</div>
      <h2 id="how-title" class="ls-section-title">Simple comme bonjour.</h2>
      <p class="ls-section-desc" style="margin:0 auto;">3 &eacute;tapes pour digitaliser votre tontine, sans perdre l&rsquo;esprit du groupe.</p>
    </div>
    <div class="ls-how__grid">
      <div class="ls-how__step ls-reveal">
        <div class="ls-how__step-number">01</div>
        <span class="ls-how__step-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17a1.17 1.17 0 0 1-1 0c-2-1.7-4.5-2.7-7-3"/><path d="M16 17a1.17 1.17 0 0 0 1 0c2-1.7 4.5-2.7 7-3"/><path d="M5.5 14.5c-1.7-1.2-3.5-3.5-4-6.5"/><path d="M18.5 14.5c1.7-1.2 3.5-3.5 4-6.5"/></svg></span>
        <h3 class="ls-how__step-title">Cr&eacute;ez ou rejoignez une tontine</h3>
        <p class="ls-how__step-desc">
          Invitez vos membres par lien ou QR code.
          D&eacute;finissez le montant, la fr&eacute;quence et le type de tontine.
        </p>
      </div>
      <div class="ls-how__step ls-how__step--featured ls-reveal ls-reveal--delay-1">
        <div class="ls-how__step-number">02</div>
        <span class="ls-how__step-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg></span>
        <h3 class="ls-how__step-title">Cotisez en un tap</h3>
        <p class="ls-how__step-desc">
          Payez directement via Wave ou Orange Money.
          Chaque paiement est enregistr&eacute; et visible par tous les membres instantan&eacute;ment.
        </p>
        <div class="ls-how__step-chips">
          <span class="ls-how__chip ls-how__chip--wave">Wave</span>
          <span class="ls-how__chip ls-how__chip--om">Orange Money</span>
        </div>
      </div>
      <div class="ls-how__step ls-reveal ls-reveal--delay-2">
        <div class="ls-how__step-number">03</div>
        <span class="ls-how__step-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span>
        <h3 class="ls-how__step-title">Recevez votre tour en toute clart&eacute;</h3>
        <p class="ls-how__step-desc">
          Rappels automatiques, suivi des paiements, tirage transparent.
          Votre score de cr&eacute;dit se construit &agrave; chaque cycle ponctuel.
        </p>
      </div>
    </div>
    <div class="ls-how__cta">
      <a href="{{ route('auth.register') }}" class="ls-btn-primary">
        Cr&eacute;er ma premi&egrave;re tontine
        <span class="ls-btn-primary__arrow" aria-hidden="true">&#8594;</span>
      </a>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
  4. TYPES DE TONTINES
════════════════════════════════ -->
<section class="ls-live" id="types" aria-labelledby="types-title">
  <div class="ls-container">
    <div class="ls-live__header">
      <div class="ls-section-badge">4 formules</div>
      <h2 id="types-title" class="ls-section-title">
        4 types de tontines.<br>
        <span class="ls-text-green">Choisissez la v&ocirc;tre.</span>
      </h2>
      <p class="ls-section-desc" style="margin:0 auto;">
        De la rotation classique aux ench&egrave;res, chaque formule a sa m&eacute;canique.
      </p>
    </div>
    <div class="ls-live__grid">
      <div class="ls-live__card ls-reveal">
        <div class="ls-live__card-top">
          <span class="ls-live__card-name">Tontine Classique</span>
          <span class="ls-live__card-badge">Rotation &amp; Tirage</span>
        </div>
        <div class="ls-live__card-meta">
          Deux modes de tirage au choix : <strong>rotation</strong> (ordre d&eacute;fini) ou <strong>tirage al&eacute;atoire</strong> (score de cr&eacute;dit booste vos chances). Chaque membre re&ccedil;oit la cagnotte une fois.
        </div>
        <div class="ls-live__card-footer" style="border:none;padding-top:8px;">
          <span style="font-size:0.75rem;color:var(--gray-400);">&#x1F4C5; Mensuel &middot; Hebdomadaire &middot; Quotidien &middot; V&eacute;to d&eacute;mocratique</span>
        </div>
      </div>
      <div class="ls-live__card ls-reveal ls-reveal--delay-2" style="border:2px solid #f59e0b;background:linear-gradient(135deg,#fffbeb,#fef3c7);position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;right:0;background:#f59e0b;color:white;font-size:0.65rem;font-weight:700;padding:3px 10px;border-bottom-left-radius:8px;letter-spacing:0.05em;">UNIQUE EN AFRIQUE</div>
        <div class="ls-live__card-top">
          <span class="ls-live__card-name">Tontine aux Ench&egrave;res</span>
          <span class="ls-live__card-badge" style="background:#f59e0b;color:white;">&#x1F3C6; Innovation</span>
        </div>
        <div class="ls-live__card-meta" style="color:#92400e;">
          <strong>Besoin urgent du pot ?</strong> Ench&eacute;rissez de 0,5&nbsp;% &agrave; 30&nbsp;% pour le recevoir en priorit&eacute;. Votre remise est redistribu&eacute;e aux autres membres — tout le monde y gagne.
        </div>
        <div class="ls-live__card-footer" style="border:none;padding-top:8px;">
          <span style="font-size:0.75rem;color:#b45309;font-weight:600;">&#x1F4CA; Ench&egrave;res en temps r&eacute;el &middot; Redistribution automatique</span>
        </div>
      </div>
      <div class="ls-live__card ls-reveal ls-reveal--delay-3">
        <div class="ls-live__card-top">
          <span class="ls-live__card-name">&Eacute;pargne Forc&eacute;e</span>
          <span class="ls-live__card-badge ls-live__card-badge--save">&Eacute;pargne</span>
        </div>
        <div class="ls-live__card-meta">
          Le groupe cotise vers un objectif commun. Le cr&eacute;ateur d&eacute;cide de la cl&ocirc;ture et chacun r&eacute;cup&egrave;re son &eacute;pargne int&eacute;gralement.
        </div>
        <div class="ls-live__card-footer" style="border:none;padding-top:8px;">
          <span style="font-size:0.75rem;color:var(--gray-400);">&#x1F3AF; Objectif collectif</span>
        </div>
      </div>
      <div class="ls-live__card ls-reveal" style="border-left:3px solid #ec4899;">
        <div class="ls-live__card-top">
          <span class="ls-live__card-name">Tontine C&eacute;r&eacute;monielle</span>
          <span class="ls-live__card-badge" style="background:#fdf2f8;color:#9d174d;">&#x1F389; &Eacute;v&eacute;nement</span>
        </div>
        <div class="ls-live__card-meta">
          Collecte pour un mariage, baptême ou événement familial. Le groupe cotise et un b&eacute;n&eacute;ficiaire d&eacute;sign&eacute; re&ccedil;oit le pot &agrave; la date de la c&eacute;r&eacute;monie.
        </div>
        <div class="ls-live__card-footer" style="border:none;padding-top:8px;">
          <span style="font-size:0.75rem;color:#9d174d;">&#x231B; Compte &agrave; rebours int&eacute;gr&eacute;</span>
        </div>
      </div>
    </div>
    <div class="ls-live__cta">
      <a href="{{ route('auth.register') }}" class="ls-live__cta-link">
        Cr&eacute;er ma tontine
        <span aria-hidden="true">&#8594;</span>
      </a>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
  5. FEATURES
════════════════════════════════ -->
<section class="ls-features" id="pourquoi" aria-labelledby="features-title">
  <div class="ls-container">
    <div class="ls-features__header">
      <div class="ls-section-badge">Ce qui nous diff&eacute;rencie</div>
      <h2 id="features-title" class="ls-section-title">Aucune autre solution ne fait &ccedil;a.<br><span class="ls-text-green">Tout &agrave; la fois.</span></h2>
      <p class="ls-section-desc" style="margin:0 auto;">
        TontineSN n&rsquo;est pas une app g&eacute;n&eacute;rique. C&rsquo;est un syst&egrave;me con&ccedil;u sp&eacute;cifiquement pour l&rsquo;usage s&eacute;n&eacute;galais, de bout en bout.
      </p>
    </div>
    <div class="ls-features__grid">
      <div class="ls-feature ls-reveal">
        <div class="ls-feature__icon ls-feature__icon--green" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg></div>
        <h3 class="ls-feature__title">PWA &mdash; z&eacute;ro installation</h3>
        <p class="ls-feature__desc">Fonctionne sur tout navigateur, m&ecirc;me en bas d&eacute;bit. Acc&egrave;s hors connexion. Aucun passage par un store.</p>
      </div>
      <div class="ls-feature ls-feature--highlight ls-reveal ls-reveal--delay-1">
        <div class="ls-feature__icon ls-feature__icon--white" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg></div>
        <h3 class="ls-feature__title">Pens&eacute; pour l&rsquo;Afrique de l&rsquo;Ouest</h3>
        <p class="ls-feature__desc">FCFA natif. Wave, Orange Money, Free Money. Interface en fran&ccedil;ais et anglais.</p>
      </div>
      <div class="ls-feature ls-reveal ls-reveal--delay-2">
        <div class="ls-feature__icon ls-feature__icon--yellow" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></div>
        <h3 class="ls-feature__title">Score de cr&eacute;dit communautaire</h3>
        <p class="ls-feature__desc">Identifiant financier informel inexistant ailleurs. Votre historique devient votre r&eacute;putation num&eacute;rique portable.</p>
        <div class="ls-feature__badges">
          <span class="ls-feature__badge ls-feature__badge--bronze">Bronze</span>
          <span class="ls-feature__badge ls-feature__badge--silver">Argent</span>
          <span class="ls-feature__badge ls-feature__badge--gold">Or</span>
        </div>
      </div>
      <div class="ls-feature ls-reveal ls-reveal--delay-3">
        <div class="ls-feature__icon ls-feature__icon--red" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
        <h3 class="ls-feature__title">KYC + 2FA + chiffrement</h3>
        <p class="ls-feature__desc">V&eacute;rification d&rsquo;identit&eacute;, double authentification, SSL. Niveau de s&eacute;curit&eacute; bancaire pour une pratique informelle.</p>
      </div>
      <div class="ls-feature ls-reveal">
        <div class="ls-feature__icon ls-feature__icon--green" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg></div>
        <h3 class="ls-feature__title">Chat de groupe par tontine</h3>
        <p class="ls-feature__desc">Coordination interne sans WhatsApp. Chaque tontine a son espace d&eacute;di&eacute;, s&eacute;cur&eacute; et horodat&eacute;.</p>
      </div>
      <div class="ls-feature ls-reveal ls-reveal--delay-1">
        <div class="ls-feature__icon ls-feature__icon--yellow" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg></div>
        <h3 class="ls-feature__title">Paiement P2P par QR code</h3>
        <p class="ls-feature__desc">Envoi direct entre membres. Scanner, confirmer, c&rsquo;est fait. Chaque &eacute;change est enregistr&eacute; et tra&ccedil;able.</p>
      </div>
      <div class="ls-feature ls-reveal ls-reveal--delay-2">
        <div class="ls-feature__icon ls-feature__icon--red" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></div>
        <h3 class="ls-feature__title">Rappels &amp; notifications automatiques</h3>
        <p class="ls-feature__desc">&Eacute;ch&eacute;ances, tours &agrave; venir, retards &mdash; chaque membre est notifi&eacute; en temps r&eacute;el. Fini les relances manuelles.</p>
      </div>
      <div class="ls-feature ls-feature--highlight ls-reveal ls-reveal--delay-3">
        <div class="ls-feature__icon ls-feature__icon--white" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 13h6"/><path d="M12 17h3"/></svg></div>
        <h3 class="ls-feature__title">Historique &amp; re&ccedil;us PDF exportables</h3>
        <p class="ls-feature__desc">Chaque paiement g&eacute;n&egrave;re un re&ccedil;u horodat&eacute;. Exportable en PDF &mdash; preuve l&eacute;gale en cas de litige.</p>
      </div>
    </div>
    <div class="ls-how__cta">
      <a href="{{ route('auth.register') }}" class="ls-btn-primary">
        Rejoindre TontineSN gratuitement
        <span class="ls-btn-primary__arrow" aria-hidden="true">&#8594;</span>
      </a>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
  6. SCORE DE CRÉDIT
════════════════════════════════ -->
<section class="ls-score" id="score" aria-labelledby="score-title">
  <div class="ls-container">
    <div class="ls-score__grid">
      <div class="ls-score__visual" aria-hidden="true">
        <div class="ls-score__mockup">
          <div class="ls-score__screen">
            <div class="ls-score__mock-header">
              <span class="ls-score__mock-back">&#8592;</span>
              <span class="ls-score__mock-title">Mon score</span>
              <span></span>
            </div>
            <div class="ls-score__mock-hero">
              <div class="ls-score__mock-ring">
                <svg viewBox="0 0 88 88" class="ls-score__ring-svg" aria-hidden="true">
                  <circle cx="44" cy="44" r="36" class="ls-score__ring-bg"/>
                  <circle cx="44" cy="44" r="36" class="ls-score__ring-fill"/>
                </svg>
                <span class="ls-score__ring-value">8.4</span>
              </div>
              <div class="ls-score__mock-badge">&#9733; Membre Or</div>
              <p class="ls-score__mock-sub">Top 12% des membres</p>
            </div>
            <div class="ls-score__mock-history">
              <div class="ls-score__mock-history-title">Derniers paiements</div>
              <div class="ls-score__mock-tx">
                <span class="ls-score__tx-dot"></span>
                <div class="ls-score__tx-info">
                  <span>Tontine Famille Diallo</span>
                  <small>25 000 FCFA &middot; 2 jan</small>
                </div>
                <span class="ls-score__tx-status"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> &Agrave; l&rsquo;heure</span>
              </div>
              <div class="ls-score__mock-tx">
                <span class="ls-score__tx-dot"></span>
                <div class="ls-score__tx-info">
                  <span>Tontine Amis Thi&egrave;s</span>
                  <small>10 000 FCFA &middot; 5 d&eacute;c</small>
                </div>
                <span class="ls-score__tx-status"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> &Agrave; l&rsquo;heure</span>
              </div>
              <div class="ls-score__mock-tx">
                <span class="ls-score__tx-dot"></span>
                <div class="ls-score__tx-info">
                  <span>Tontine Famille Diallo</span>
                  <small>25 000 FCFA &middot; 2 d&eacute;c</small>
                </div>
                <span class="ls-score__tx-status"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> &Agrave; l&rsquo;heure</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div>
        <div class="ls-section-badge">Score de cr&eacute;dit</div>
        <h2 id="score-title" class="ls-section-title">
          Votre capital de confiance<br>
          <span class="ls-text-green">construit paiement apr&egrave;s paiement.</span>
        </h2>
        <p class="ls-score__desc">
          Dans les syst&egrave;mes bancaires formels, on construit un historique de cr&eacute;dit.
          Dans la tontine informelle, cette donn&eacute;e disparaissait.
          TontineSN la capture, la valorise et la rend <strong>portable entre tous vos groupes</strong>.
        </p>
        <div class="ls-score__innovation-tag">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg> Seule plateforme &agrave; proposer un score de cr&eacute;dit communautaire pour la tontine
        </div>
        <div class="ls-score__levels">
          <div class="ls-score__level">
            <div class="ls-score__level-icon ls-score__level-icon--bronze" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
            <div>
              <strong class="ls-score__level-name">Bronze</strong>
              <p class="ls-score__level-desc">Score 0 &ndash; 4 &middot; Membre d&eacute;butant</p>
            </div>
          </div>
          <div class="ls-score__level">
            <div class="ls-score__level-icon ls-score__level-icon--silver" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
            <div>
              <strong class="ls-score__level-name">Argent</strong>
              <p class="ls-score__level-desc">Score 4 &ndash; 7 &middot; Membre fiable</p>
            </div>
          </div>
          <div class="ls-score__level ls-score__level--active">
            <div class="ls-score__level-icon ls-score__level-icon--gold" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
            <div>
              <strong class="ls-score__level-name">Or</strong>
              <p class="ls-score__level-desc">Score 7 &ndash; 10 &middot; Membre de confiance</p>
            </div>
          </div>
        </div>
        <div class="ls-score__stats">
          <div class="ls-score__stat ls-reveal">
            <span class="ls-score__stat-value" data-count="100">100%</span>
            <span class="ls-score__stat-label">Transactions<br>tra&ccedil;ables</span>
          </div>
          <div class="ls-score__stat ls-reveal ls-reveal--delay-1">
            <span class="ls-score__stat-value" data-count="4">4</span>
            <span class="ls-score__stat-label">Moyens de<br>paiement</span>
          </div>

        </div>
        <div class="ls-score__quote">
          Votre ponctualit&eacute; n&rsquo;&eacute;tait jamais r&eacute;compens&eacute;e. Avec TontineSN, elle devient votre identit&eacute; financi&egrave;re.<br>
          <strong>C&rsquo;est &ccedil;a, l&rsquo;inclusion financi&egrave;re par la communaut&eacute;.</strong>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
  7. VISION
════════════════════════════════ -->
<section class="ls-vision" aria-labelledby="vision-title">
  <div class="ls-container">
    <div class="ls-vision__grid">
      <div class="ls-vision__content">
        <div class="ls-section-badge">Notre vision</div>
        <h2 id="vision-title" class="ls-section-title">
          La tontine n&rsquo;est pas juste<br>
          <span class="ls-text-green">une habitude. C&rsquo;est une infrastructure.</span>
        </h2>
        <p class="ls-vision__desc">
          Des milliards de FCFA circulent chaque ann&eacute;e dans les tontines s&eacute;n&eacute;galaises &mdash;
          sans tra&ccedil;abilit&eacute;, sans historique, sans identit&eacute; financi&egrave;re pour les membres.
        </p>
        <p class="ls-vision__desc">
          TontineSN transforme chaque cotisation en donn&eacute;e financi&egrave;re. Chaque membre construit un
          <strong>capital de confiance num&eacute;rique</strong> portable dans toutes ses tontines futures.
        </p>
        <div class="ls-vision__pillars">
          <div class="ls-vision__pillar">
            <span class="ls-vision__pillar-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></span>
            <div>
              <strong>Inclusion financi&egrave;re</strong>
              <p>Donner une identit&eacute; financi&egrave;re &agrave; ceux qui n&rsquo;ont pas acc&egrave;s au cr&eacute;dit formel</p>
            </div>
          </div>
          <div class="ls-vision__pillar">
            <span class="ls-vision__pillar-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
            <div>
              <strong>100% s&eacute;n&eacute;galais</strong>
              <p>Con&ccedil;u &agrave; Dakar, pour les familles, associations et groupes professionnels du S&eacute;n&eacute;gal</p>
            </div>
          </div>
          <div class="ls-vision__pillar">
            <span class="ls-vision__pillar-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17a1.17 1.17 0 0 1-1 0c-2-1.7-4.5-2.7-7-3"/><path d="M16 17a1.17 1.17 0 0 0 1 0c2-1.7 4.5-2.7 7-3"/><path d="M5.5 14.5c-1.7-1.2-3.5-3.5-4-6.5"/><path d="M18.5 14.5c1.7-1.2 3.5-3.5 4-6.5"/></svg></span>
            <div>
              <strong>Lien social pr&eacute;serv&eacute;</strong>
              <p>La technologie renforce la confiance, elle ne remplace pas le groupe</p>
            </div>
          </div>
        </div>
      </div>
      <div class="ls-vision__numbers" aria-hidden="true">
        <div class="ls-vision__number-card ls-vision__number-card--main">
          <span class="ls-vision__number">2M+</span>
          <span class="ls-vision__number-label">pratiquants de tontine<br>au S&eacute;n&eacute;gal</span>
        </div>
        <div class="ls-vision__number-card">
          <span class="ls-vision__number">&#8805;&nbsp;80%</span>
          <span class="ls-vision__number-label">des transactions<br>encore informelles</span>
        </div>
        <div class="ls-vision__number-card ls-vision__number-card--green">
          <span class="ls-vision__number">0 FCFA</span>
          <span class="ls-vision__number-label">de frais<br>pour TontineSN</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
  8. PAYMENT
════════════════════════════════ -->
<section class="ls-payment" aria-labelledby="payment-title">
  <div class="ls-container">
    <div class="ls-payment__grid">
      <div class="ls-payment__image-wrap">
        <img
          srcset="{{ asset('images/mobile-payment.webp') }} 1x"
          src="{{ asset('images/mobile-payment.jpg') }}"
          alt="Paiement mobile Wave et Orange Money pour tontine au Sénégal"
          class="ls-payment__image"
          width="600" height="400"
          loading="lazy"
        >
        <div class="ls-payment__image-overlay">
          <img src="{{ asset('images/paytech logo.png') }}" alt="PayTech" class="ls-payment__overlay-img" width="40" height="28" loading="lazy">
          <span class="ls-payment__overlay-text">S&eacute;curis&eacute; par PayTech</span>
        </div>
      </div>
      <div>
        <div class="ls-section-badge">Paiement s&eacute;curis&eacute;</div>
        <h2 id="payment-title" class="ls-payment__title">
          Payez depuis n&rsquo;importe o&ugrave;,<br>
          <span class="ls-text-green">sur n&rsquo;importe quel appareil.</span>
        </h2>
        <p class="ls-payment__desc">
          Mobile, tablette ou ordinateur &mdash; TontineSN accepte tous les moyens de paiement locaux.
          Chaque transaction est enregistr&eacute;e et visible par tous les membres en temps r&eacute;el.
        </p>
        <div class="ls-payment__methods">
          <div class="ls-payment__method ls-payment__method--wave">
            <img src="{{ asset('images/logo wave.png') }}" alt="Wave" class="ls-payment__method-img" width="44" height="44" loading="lazy">
            <span class="ls-payment__method-name">Wave</span>
          </div>
          <div class="ls-payment__method ls-payment__method--om">
            <img src="{{ asset('images/logo orange money.png') }}" alt="Orange Money" class="ls-payment__method-img" width="44" height="44" loading="lazy">
            <span class="ls-payment__method-name">Orange Money</span>
          </div>
          <div class="ls-payment__method ls-payment__method--free">
            <img src="{{ asset('images/logo free money.svg') }}" alt="Free Money" class="ls-payment__method-img" width="44" height="44" loading="lazy">
            <span class="ls-payment__method-name">Free Money</span>
          </div>
          <div class="ls-payment__method ls-payment__method--card">
            <img src="{{ asset('images/carte bancaire.png') }}" alt="Carte bancaire" class="ls-payment__method-img" width="44" height="44" loading="lazy">
            <span class="ls-payment__method-name">Carte</span>
          </div>
        </div>
        <div class="ls-payment__powered">
          <span class="ls-payment__powered-text">Transactions s&eacute;curis&eacute;es par</span>
          <img src="{{ asset('images/paytech logo.png') }}" alt="PayTech" class="ls-payment__powered-img" height="20" loading="lazy">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
   10. TRUST BAND
════════════════════════════════ -->
<section class="ls-trust-band" aria-label="Garanties et sécurité">
  <div class="ls-container">
    <div class="ls-trust-band__label">Pourquoi nous faire confiance</div>
    <div class="ls-trust-band__grid">
      <div class="ls-trust-band__item">
        <span class="ls-trust-band__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg></span>
        <div>
          <strong>Paiements s&eacute;curis&eacute;s</strong>
          <span>Transactions via PayTech &mdash; agr&eacute;&eacute; Banque Centrale</span>
        </div>
      </div>
      <div class="ls-trust-band__item">
        <span class="ls-trust-band__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg></span>
        <div>
          <strong>KYC v&eacute;rifi&eacute;</strong>
          <span>Chaque membre est identifi&eacute; et valid&eacute; par notre &eacute;quipe</span>
        </div>
      </div>
      <div class="ls-trust-band__item">
        <span class="ls-trust-band__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h.01"/><path d="M12 12h.01"/><path d="M15 12h.01"/><path d="M9 16h.01"/><path d="M12 16h.01"/><path d="M15 16h.01"/></svg></span>
        <div>
          <strong>Aucun fonds d&eacute;tenu</strong>
          <span>L&rsquo;argent va directement de membre &agrave; membre. TontineSN ne touche pas aux fonds.</span>
        </div>
      </div>
      <div class="ls-trust-band__item">
        <span class="ls-trust-band__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 13h6"/><path d="M12 17h3"/></svg></span>
        <div>
          <strong>Mentions l&eacute;gales compl&egrave;tes</strong>
          <span><a href="{{ route('cgu') }}">CGU</a> &middot; <a href="{{ route('privacy') }}">Confidentialit&eacute;</a> &middot; <a href="{{ route('mentions') }}">Mentions l&eacute;gales</a></span>
        </div>
      </div>
    </div>
    <div class="ls-trust-band__powered">
      <span>Paiements s&eacute;curis&eacute;s par</span>
      <img src="{{ asset('images/paytech logo.png') }}" alt="PayTech" height="22" loading="lazy">
    </div>
  </div>
</section>

<!-- ════════════════════════════════
   11. FAQ
════════════════════════════════ -->
<section class="ls-faq" id="faq-section" aria-labelledby="faq-title">
  <div class="ls-container">
    <div class="ls-faq__header">
      <div class="ls-section-badge">Questions fr&eacute;quentes</div>
      <h2 id="faq-title" class="ls-section-title">Vous avez des questions.<br><span class="ls-text-green">Voici les r&eacute;ponses.</span></h2>
    </div>
    <div class="ls-faq__grid">
      <details class="ls-faq__item ls-reveal">
        <summary class="ls-faq__question">Que se passe-t-il si un membre ne paie pas ?</summary>
        <div class="ls-faq__answer">
          La plateforme envoie des rappels automatiques avant l&rsquo;&eacute;ch&eacute;ance. En cas de non-paiement, le score du membre baisse visiblement. Le cr&eacute;ateur peut exclure un membre d&eacute;faillant. Chaque retard est consign&eacute; dans l&rsquo;historique, visible par tout le groupe.
        </div>
      </details>
      <details class="ls-faq__item ls-reveal ls-reveal--delay-1">
        <summary class="ls-faq__question">Est-ce vraiment gratuit ?</summary>
        <div class="ls-faq__answer">
          Oui. La cr&eacute;ation de compte et la gestion de tontines sont enti&egrave;rement gratuites. Les seuls frais sont ceux de votre op&eacute;rateur (Wave, Orange Money) qui s&rsquo;appliquent &agrave; toute transaction mobile, ind&eacute;pendamment de TontineSN.
        </div>
      </details>
      <details class="ls-faq__item ls-reveal ls-reveal--delay-2">
        <summary class="ls-faq__question">TontineSN touche-t-il &agrave; mon argent ?</summary>
        <div class="ls-faq__answer">
          Non. TontineSN est une plateforme de gestion et de suivi. Les paiements transitent directement entre vous et vos membres via Wave ou Orange Money. Nous ne d&eacute;tenons aucun fonds &agrave; aucun moment.
        </div>
      </details>
      <details class="ls-faq__item ls-reveal ls-reveal--delay-3">
        <summary class="ls-faq__question">Comment rejoindre une tontine sans cr&eacute;er de compte ?</summary>
        <div class="ls-faq__answer">
          Vous devez cr&eacute;er un compte gratuit pour rejoindre une tontine &mdash; cela garantit la tra&ccedil;abilit&eacute; et la confiance entre membres. L&rsquo;inscription prend moins de 2 minutes avec votre email ou via Google.
        </div>
      </details>
      <details class="ls-faq__item ls-reveal">
        <summary class="ls-faq__question">Mes donn&eacute;es personnelles sont-elles prot&eacute;g&eacute;es ?</summary>
        <div class="ls-faq__answer">
          Oui. Connexion chiffr&eacute;e SSL, mots de passe hach&eacute;s, double authentification (2FA) disponible. Vous pouvez exporter ou supprimer vos donn&eacute;es &agrave; tout moment conform&eacute;ment &agrave; notre politique de confidentialit&eacute;.
        </div>
      </details>
      <details class="ls-faq__item ls-reveal ls-reveal--delay-1">
        <summary class="ls-faq__question">Combien de membres peut contenir une tontine ?</summary>
        <div class="ls-faq__answer">
          Une tontine peut accueillir jusqu&rsquo;&agrave; 50 membres. Pour les grands groupes, vous pouvez cr&eacute;er plusieurs tontines distinctes et les g&eacute;rer depuis le m&ecirc;me tableau de bord.
        </div>
      </details>
    </div>
    <div class="ls-faq__more">
      <a href="{{ route('faq.index') }}" class="ls-faq__more-link">
        Voir toutes les questions
        <span aria-hidden="true">&#8594;</span>
      </a>
    </div>
  </div>
</section>

<!-- ════════════════════════════════
   12. CTA FINAL
════════════════════════════════ -->
<section class="ls-cta" aria-labelledby="cta-title">
  @php $tontineCount = \App\Models\Tontine::whereIn('status', ['active', 'pending'])->count(); @endphp
  <div class="ls-cta__bg" aria-hidden="true"></div>
  <div class="ls-cta__inner">
    <div class="ls-cta__image-wrap">
      <img
        srcset="{{ asset('images/tontine-une dame-qui manage.webp') }} 1x"
        src="{{ asset('images/tontine-une dame-qui manage.jfif') }}"
        alt="Dame gérant sa tontine en ligne au Sénégal"
        class="ls-cta__image"
        width="440" height="520"
        loading="lazy"
      >
      <div class="ls-cta__image-badge">
        <span class="ls-cta__image-badge-dot" aria-hidden="true"></span>
        <span>{{ $tontineCount }}+ tontines actives</span>
      </div>
    </div>
    <div>
      <h2 id="cta-title" class="ls-cta__title">
        Votre tontine mérite mieux<br>qu'un carnet papier.
        <span class="ls-cta__title-sub">Rejoignez des centaines de tontines actives dès aujourd'hui.</span>
      </h2>
      <p class="ls-cta__subtitle">
        Cr&eacute;ez votre premi&egrave;re tontine en moins de 2 minutes.
        Invitez vos membres par lien ou QR code.<br><strong>Toujours gratuit.</strong>
      </p>
      <a href="{{ route('auth.register') }}" class="ls-cta__btn">
        Commencer gratuitement
        <span class="ls-cta__btn-arrow" aria-hidden="true">&#8594;</span>
      </a>
      <div class="ls-cta__reassurance">
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          Aucune carte bancaire requise
        </span>
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          Inscription en 2 minutes
        </span>
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          Wave &amp; Orange Money
        </span>
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          Votre score commence d&egrave;s le 1er paiement
        </span>
      </div>
      <div class="ls-cta__counter">
        <span class="ls-cta__counter-number" data-count="{{ $tontineCount }}">0</span>
        <span class="ls-cta__counter-label">tontines actives</span>
      </div>
    </div>
  </div>
</section>

</main>

<!-- FOOTER -->
<footer class="ls-footer" role="contentinfo">
  <div class="ls-footer__inner">
    <div class="ls-footer__brand">
      <picture>
        <source srcset="{{ asset('images/Tontine.webp') }}" type="image/webp">
        <img src="{{ asset('images/Tontine.png') }}" alt="Logo TontineSN - Tontine numérique Sénégal" class="ls-footer__logo" width="36" height="36" loading="lazy">
      </picture>
      <p class="ls-footer__tagline">La tontine num&eacute;rique du S&eacute;n&eacute;gal.</p>
      <p class="ls-footer__tagline ls-footer__tagline--sub">Pour les familles, associations &amp; professionnels</p>
      <a href="mailto:contact@tontinesn.com" class="ls-footer__contact">contact@tontinesn.com</a>
      <a href="https://wa.me/221781620888?text=Bonjour%20TontineSN%2C%20j%27ai%20besoin%20d%27aide" target="_blank" rel="noreferrer" class="ls-footer__contact" style="display:flex;align-items:center;gap:6px;margin-top:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        Support WhatsApp
      </a>
    </div>
    <div class="ls-footer__col">
      <span class="ls-footer__col-title">Produit</span>
      <a href="#comment">Comment &ccedil;a marche</a>
      <a href="#types">Types de tontines</a>
      <a href="#pourquoi">Fonctionnalit&eacute;s</a>
      <a href="#score">Score de cr&eacute;dit</a>
    </div>
    <div class="ls-footer__col">
      <span class="ls-footer__col-title">Ressources</span>
      <a href="{{ route('faq.index') }}">FAQ</a>
      <a href="{{ route('api.docs') }}">API</a>
    </div>
    <div class="ls-footer__col">
      <span class="ls-footer__col-title">L&eacute;gal</span>
      <a href="{{ route('cgu') }}">CGU</a>
      <a href="{{ route('mentions') }}">Mentions l&eacute;gales</a>
      <a href="{{ route('privacy') }}">Confidentialit&eacute;</a>
    </div>
  </div>
  <div class="ls-footer__bottom">
    <span>&copy; {{ date('Y') }} TontineSN &mdash; Fait au S&eacute;n&eacute;gal &#9733;</span>
    <div class="ls-footer__links">
      <a href="{{ route('faq.index') }}">FAQ</a>
      <a href="mailto:contact@tontinesn.com">Contact</a>
      <a href="{{ route('cgu') }}">CGU</a>
      <a href="{{ route('privacy') }}">Confidentialit&eacute;</a>
    </div>
  </div>
</footer>

<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
  'use strict';

  // Nav scroll
  var nav = document.getElementById('nav');
  var ticking = false;
  window.addEventListener('scroll', function() {
    if (!ticking) {
      requestAnimationFrame(function() {
        nav.classList.toggle('ls-nav--scrolled', window.scrollY > 40);
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });

  // Burger
  var burger = document.getElementById('burgerBtn');
  var mobile = document.getElementById('mobileMenu');
  burger.addEventListener('click', function() {
    var open = mobile.classList.toggle('ls-mobile--open');
    burger.classList.toggle('ls-burger--open');
    burger.setAttribute('aria-expanded', open);
  });
  mobile.querySelectorAll('a').forEach(function(link) {
    link.addEventListener('click', function() {
      mobile.classList.remove('ls-mobile--open');
      burger.classList.remove('ls-burger--open');
      burger.setAttribute('aria-expanded', 'false');
    });
  });

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  // Scroll reveal
  try {
    var revealObs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('ls-reveal--visible'); });
    }, { threshold: 0.08, rootMargin: '0px 0px -20px 0px' });
    document.querySelectorAll('.ls-reveal').forEach(function(el) { revealObs.observe(el); });
  } catch(e) {
    document.querySelectorAll('.ls-reveal').forEach(function(el) { el.classList.add('ls-reveal--visible'); });
  }
  // Fallback: force visible after 3s in case observer never fires
  setTimeout(function() {
    document.querySelectorAll('.ls-reveal:not(.ls-reveal--visible)').forEach(function(el) {
      el.classList.add('ls-reveal--visible');
    });
  }, 3000);

  // Counters
  function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(function(el) {
      var target = parseInt(el.getAttribute('data-count'), 10);
      var suffix = el.textContent.replace(/[\d]/g, '');
      var current = 0;
      var step = Math.max(1, Math.floor(target / 40));
      var timer = setInterval(function() {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = current + suffix;
      }, 30);
    });
  }
  var counted = false;
  var countObs = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting && !counted) {
        counted = true;
        animateCounters();
        countObs.disconnect();
      }
    });
  }, { threshold: 0.3 });
  var stats = document.querySelector('.ls-score__stats');
  if (stats) countObs.observe(stats);
  var ctaCounter = document.querySelector('.ls-cta__counter');
  if (ctaCounter) countObs.observe(ctaCounter);

  // Active nav on scroll
  var navLinks = document.querySelectorAll('.ls-nav__link[href^="#"]');
  var sectionObs = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        navLinks.forEach(function(link) {
          link.classList.toggle('ls-nav__link--active', link.getAttribute('href') === '#' + entry.target.id);
        });
      }
    });
  }, { threshold: 0.4 });
  document.querySelectorAll('section[id]').forEach(function(s) { sectionObs.observe(s); });

})();
</script>
</body>
</html>
