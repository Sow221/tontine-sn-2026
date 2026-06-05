<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#009639">
<meta name="description" content="TontineSN — Cotiser simple. Recevoir sûr. Créez et gérez vos tontines en ligne avec Wave & Orange Money, la tontine numérique du Sénégal.">
<meta property="og:title" content="TontineSN — La tontine numérique du Sénégal">
<meta property="og:description" content="Cotiser simple. Recevoir sûr. Gérez vos tontines en ligne avec Wave & Orange Money.">
<meta property="og:image" content="{{ asset('images/Tontine.png') }}">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:type" content="website">
<link rel="canonical" href="{{ url('/') }}">
<link href="{{ asset('css/landing.css') }}" rel="stylesheet">
<title>TontineSN — La tontine numérique du Sénégal</title>
</head>
<body>

<!-- SKIP LINK -->
<a href="#main-content" class="ls-skip">Aller au contenu principal</a>

<!-- HEADER / NAV -->
<header role="banner">
<nav class="ls-nav" id="nav" aria-label="Navigation principale">
  <div class="ls-nav__inner">
    <a href="{{ route('home') }}" class="ls-nav__logo" aria-label="TontineSN — Accueil">
      <img src="{{ asset('images/element-logo.png') }}" alt="" class="ls-nav__logo-img" width="38" height="38" aria-hidden="true">
      <span class="ls-nav__logo-text">TontineSN</span>
    </a>
    <div class="ls-nav__links">
      <a href="#comment" class="ls-nav__link">Comment ça marche</a>
      <a href="#pourquoi" class="ls-nav__link">Pourquoi nous</a>
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
  <div class="ls-mobile" id="mobileMenu" role="menu">
    <a href="#comment" role="menuitem">Comment ça marche</a>
    <a href="#pourquoi" role="menuitem">Pourquoi nous</a>
    <hr style="border:none;border-top:1px solid var(--gray-200);margin:8px 0;">
    <a href="{{ route('auth.register') }}" role="menuitem" style="display:block;background:var(--green);color:white;text-align:center;padding:12px;border-radius:var(--radius-full);font-weight:700;border:none;margin-top:8px;">Commencer gratuitement</a>
  </div>
</nav>
</header>

<!-- MAIN -->
<main id="main-content">

<!-- HERO -->
<section class="ls-hero" aria-label="Présentation">
  <div class="ls-hero__bg" aria-hidden="true"></div>
  <div class="ls-hero__star" aria-hidden="true">&#9733;</div>
  <div class="ls-hero__inner">
    <div class="ls-hero__content">
      <div class="ls-hero__badge">
        <span class="ls-hero__badge-star" aria-hidden="true">&#9733;</span>
        Fait au S&eacute;n&eacute;gal, pour le S&eacute;n&eacute;gal
      </div>
      <h1 class="ls-hero__title">
        Votre tontine,<br>
        <span class="ls-hero__title-accent">enfin num&eacute;rique.</span>
      </h1>
      <p class="ls-hero__slogan">Cotiser simple. Recevoir s&ucirc;r.</p>
      <p class="ls-hero__desc">
        TontineSN vous permet de cr&eacute;er, g&eacute;rer et cotiser dans vos tontines &mdash;
        via smartphone ou simple t&eacute;l&eacute;phone, en toute transparence.
      </p>
      <div class="ls-hero__actions">
        <a href="{{ route('auth.register') }}" class="ls-hero__cta">
          Rejoindre gratuitement
          <span class="ls-hero__cta-arrow" aria-hidden="true">&#8594;</span>
        </a>
        <a href="#comment" class="ls-hero__link">
          Voir comment &ccedil;a marche
          <span class="ls-hero__link-chevron" aria-hidden="true">&#8595;</span>
        </a>
      </div>
      <div class="ls-hero__trust">
        <span class="ls-hero__trust-item">
          <span class="ls-hero__trust-check" aria-hidden="true">&#10003;</span>
          Toujours gratuit
        </span>
        <span class="ls-hero__trust-item">
          <span class="ls-hero__trust-check" aria-hidden="true">&#10003;</span>
          Wave &amp; Orange Money
        </span>
        <span class="ls-hero__trust-item">
          <span class="ls-hero__trust-check" aria-hidden="true">&#10003;</span>
          Score de cr&eacute;dit inclus
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
        <div class="ls-mockup__float ls-mockup__float--1" aria-hidden="true">&#10004; Cotisation re&ccedil;ue</div>
        <div class="ls-mockup__float ls-mockup__float--2" aria-hidden="true">&#128276; Votre tour ce mois !</div>
        <div class="ls-mockup__float ls-mockup__float--3" aria-hidden="true">&#128200; Score : Or &#9733;</div>
      </div>
    </div>
  </div>
  <div class="ls-hero__wave" aria-hidden="true">
    <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="#FDF8F0"/>
    </svg>
  </div>
</section>

<!-- PROBLEM -->
<section class="ls-problem" aria-labelledby="problem-title">
  <div class="ls-container">
    <div class="ls-problem__header">
      <h2 id="problem-title" class="ls-section-title">
        La tontine, &ccedil;a marche depuis toujours.<br>
        <span style="font-weight:400;color:var(--gray-300);">Le carnet papier, beaucoup moins.</span>
      </h2>
      <p class="ls-section-desc" style="margin:0 auto;">
        Oublis de paiement. Carnets perdus. Conflits sur l'ordre des tours.
        Membres absents le jour de la collecte.
      </p>
    </div>
    <div class="ls-problem__grid">
      <div class="ls-problem__card ls-reveal">
        <span class="ls-problem__icon" aria-hidden="true">&#128467;</span>
        <p class="ls-problem__text">Carnets papier perdus ou ab&icirc;m&eacute;s</p>
      </div>
      <div class="ls-problem__card ls-reveal ls-reveal--delay-1">
        <span class="ls-problem__icon" aria-hidden="true">&#128545;</span>
        <p class="ls-problem__text">Conflits sur l'ordre des tours</p>
      </div>
      <div class="ls-problem__card ls-reveal ls-reveal--delay-2">
        <span class="ls-problem__icon" aria-hidden="true">&#127939;</span>
        <p class="ls-problem__text">Membres absents &agrave; la collecte</p>
      </div>
      <div class="ls-problem__card ls-reveal ls-reveal--delay-3">
        <span class="ls-problem__icon" aria-hidden="true">&#129335;</span>
        <p class="ls-problem__text">Aucune tra&ccedil;abilit&eacute; des paiements</p>
      </div>
    </div>
    <div class="ls-problem__bridge">
      <span class="ls-problem__bridge-line" aria-hidden="true"></span>
      <span class="ls-problem__bridge-text">TontineSN r&eacute;sout tout &ccedil;a &mdash; sans changer ce qui fait la force de votre tontine.</span>
      <span class="ls-problem__bridge-line--solid" aria-hidden="true"></span>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="ls-how" id="comment" aria-labelledby="how-title">
  <div class="ls-container">
    <div class="ls-how__header">
      <div class="ls-section-badge">Comment &ccedil;a marche</div>
      <h2 id="how-title" class="ls-section-title">Simple comme bonjour.</h2>
      <p class="ls-section-desc" style="margin:0 auto;">3 &eacute;tapes pour digitaliser votre tontine, sans perdre l'esprit du groupe.</p>
    </div>
    <div class="ls-how__grid">
      <div class="ls-how__step ls-reveal">
        <div class="ls-how__step-number">01</div>
        <span class="ls-how__step-icon" aria-hidden="true">&#129309;</span>
        <h3 class="ls-how__step-title">Cr&eacute;ez ou rejoignez une tontine</h3>
        <p class="ls-how__step-desc">
          Invitez vos membres par lien ou code.
          D&eacute;finissez le montant, la fr&eacute;quence et l'ordre des tours.
        </p>
      </div>
      <div class="ls-how__step ls-how__step--featured ls-reveal ls-reveal--delay-1">
        <div class="ls-how__step-number">02</div>
        <span class="ls-how__step-icon" aria-hidden="true">&#128184;</span>
        <h3 class="ls-how__step-title">Cotisez en un tap</h3>
        <p class="ls-how__step-desc">
          Payez directement via Wave ou Orange Money.
          Chaque paiement est enregistr&eacute; et visible par tous les membres.
        </p>
        <div class="ls-how__step-chips">
          <span class="ls-how__chip ls-how__chip--wave">Wave</span>
          <span class="ls-how__chip ls-how__chip--om">Orange Money</span>
        </div>
      </div>
      <div class="ls-how__step ls-reveal ls-reveal--delay-2">
        <div class="ls-how__step-number">03</div>
        <span class="ls-how__step-icon" aria-hidden="true">&#127919;</span>
        <h3 class="ls-how__step-title">Recevez votre tour en toute clart&eacute;</h3>
        <p class="ls-how__step-desc">
          Rappels automatiques, suivi des paiements et tirage encadr&eacute; par le cr&eacute;ateur.
          Chacun sait o&ugrave; il en est dans la rotation.
        </p>
      </div>
    </div>
    <div class="ls-how__cta">
      <a href="{{ route('auth.login') }}" class="ls-btn-primary">
        Cr&eacute;er ma premi&egrave;re tontine
        <span class="ls-btn-primary__arrow" aria-hidden="true">&#8594;</span>
      </a>
    </div>
  </div>
</section>

<!-- PAYMENT -->
<section class="ls-payment" aria-labelledby="payment-title">
  <div class="ls-container">
    <div class="ls-payment__grid">
      <div class="ls-payment__image-wrap">
        <img
          src="{{ asset('images/image_argent.jpg') }}"
          alt="Paiement mobile au S&eacute;n&eacute;gal"
          class="ls-payment__image"
          width="600" height="400"
          loading="lazy"
        >
        <div class="ls-payment__image-overlay">
          <img src="{{ asset('images/paytech-photo usage.jfif') }}" alt="" class="ls-payment__overlay-img" width="40" height="28" loading="lazy" aria-hidden="true">
          <span class="ls-payment__overlay-text">Multi-plateforme</span>
        </div>
      </div>
      <div>
        <div class="ls-payment__badge">Paiement s&eacute;curis&eacute;</div>
        <h2 id="payment-title" class="ls-payment__title">
          Payez depuis n'importe o&ugrave;,<br>
          <span class="ls-text-green">sur n'importe quel appareil.</span>
        </h2>
        <p class="ls-payment__desc">
          Mobile, tablette ou ordinateur &mdash; TontineSN accepte tous les moyens de paiement
          locaux. Chaque transaction est enregistr&eacute;e et visible par tous les membres.
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

<!-- FEATURES -->
<section class="ls-features" id="pourquoi" aria-labelledby="features-title">
  <div class="ls-container">
    <div class="ls-features__header">
      <div class="ls-section-badge">Pourquoi TontineSN</div>
      <h2 id="features-title" class="ls-section-title">Fait pour tout le monde.<br><span class="ls-text-green">Vraiment.</span></h2>
      <p class="ls-section-desc" style="margin:0 auto;">
        Pas une app de plus. Une solution pens&eacute;e pour la r&eacute;alit&eacute; s&eacute;n&eacute;galaise.
      </p>
    </div>
    <div class="ls-features__grid">
      <div class="ls-feature ls-reveal">
        <div class="ls-feature__icon ls-feature__icon--green" aria-hidden="true">&#128241;</div>
        <h3 class="ls-feature__title">Smartphone ou t&eacute;l&eacute;phone basique</h3>
        <p class="ls-feature__desc">
          Ouvrez votre navigateur et c'est tout. Aucune app &agrave; t&eacute;l&eacute;charger &mdash; fonctionne sur tout t&eacute;l&eacute;phone avec internet.
        </p>
      </div>
      <div class="ls-feature ls-feature--highlight ls-reveal ls-reveal--delay-1">
        <div class="ls-feature__icon ls-feature__icon--white" aria-hidden="true">&#127480;</div>
        <h3 class="ls-feature__title">Fait pour le S&eacute;n&eacute;gal</h3>
        <p class="ls-feature__desc">
          Interface pens&eacute;e pour la r&eacute;alit&eacute; locale. Noms, montants en FCFA, groupes familiaux et de quartier.
        </p>
      </div>
      <div class="ls-feature ls-reveal ls-reveal--delay-2">
        <div class="ls-feature__icon ls-feature__icon--yellow" aria-hidden="true">&#128202;</div>
        <h3 class="ls-feature__title">Score de cr&eacute;dit communautaire</h3>
        <p class="ls-feature__desc">
          Chaque membre construit sa r&eacute;putation de paiement. Un gage de confiance pour toute la communaut&eacute;.
        </p>
        <div class="ls-feature__badges">
          <span class="ls-feature__badge ls-feature__badge--bronze">Bronze</span>
          <span class="ls-feature__badge ls-feature__badge--silver">Argent</span>
          <span class="ls-feature__badge ls-feature__badge--gold">Or</span>
        </div>
      </div>
      <div class="ls-feature ls-reveal ls-reveal--delay-3">
        <div class="ls-feature__icon ls-feature__icon--red" aria-hidden="true">&#128274;</div>
        <h3 class="ls-feature__title">S&eacute;curit&eacute; sans complexit&eacute;</h3>
        <p class="ls-feature__desc">
          Connexion s&eacute;curis&eacute;e par email et mot de passe. Vos paiements sont prot&eacute;g&eacute;s et tra&ccedil;ables &agrave; chaque instant.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- SCORE -->
<section class="ls-score" id="score" aria-labelledby="score-title">
  <div class="ls-container">
    <div class="ls-score__grid">
      <div class="ls-score__visual" aria-hidden="true">
        <div class="ls-score__mockup">
          <div class="ls-score__screen">
            <div class="ls-score__mock-header">
              <span class="ls-score__mock-back" aria-hidden="true">&#8592;</span>
              <span class="ls-score__mock-title">Mon score</span>
              <span></span>
            </div>
            <div class="ls-score__mock-hero">
              <div class="ls-score__mock-ring">
                <svg viewBox="0 0 88 88" class="ls-score__ring-svg" aria-hidden="true">
                  <circle cx="44" cy="44" r="36" class="ls-score__ring-bg"/>
                  <circle cx="44" cy="44" r="36" class="ls-score__ring-fill" id="scoreRingFill"/>
                </svg>
                <span class="ls-score__ring-value" id="scoreValue">8.4</span>
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
                <span class="ls-score__tx-status">&#10004; &Agrave; l'heure</span>
              </div>
              <div class="ls-score__mock-tx">
                <span class="ls-score__tx-dot"></span>
                <div class="ls-score__tx-info">
                  <span>Tontine Amis Thi&egrave;s</span>
                  <small>10 000 FCFA &middot; 5 d&eacute;c</small>
                </div>
                <span class="ls-score__tx-status">&#10004; &Agrave; l'heure</span>
              </div>
              <div class="ls-score__mock-tx">
                <span class="ls-score__tx-dot"></span>
                <div class="ls-score__tx-info">
                  <span>Tontine Famille Diallo</span>
                  <small>25 000 FCFA &middot; 2 d&eacute;c</small>
                </div>
                <span class="ls-score__tx-status">&#10004; &Agrave; l'heure</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div>
        <div class="ls-section-badge">Score de cr&eacute;dit</div>
        <h2 id="score-title" class="ls-section-title">
          Votre r&eacute;putation,<br>
          <span class="ls-text-green">visible et v&eacute;rifiable.</span>
        </h2>
        <p class="ls-score__desc">
          Chaque paiement &agrave; l'heure renforce votre score. Chaque retard le baisse.
          Un historique transparent qui parle pour vous &mdash; dans cette tontine et dans les suivantes.
        </p>
        <div class="ls-score__levels">
          <div class="ls-score__level">
            <div class="ls-score__level-icon ls-score__level-icon--bronze" aria-hidden="true">&#9733;</div>
            <div>
              <strong class="ls-score__level-name">Bronze</strong>
              <p class="ls-score__level-desc">Score 0 &ndash; 4 &middot; Membre d&eacute;butant</p>
            </div>
          </div>
          <div class="ls-score__level">
            <div class="ls-score__level-icon ls-score__level-icon--silver" aria-hidden="true">&#9733;</div>
            <div>
              <strong class="ls-score__level-name">Argent</strong>
              <p class="ls-score__level-desc">Score 4 &ndash; 7 &middot; Membre fiable</p>
            </div>
          </div>
          <div class="ls-score__level ls-score__level--active">
            <div class="ls-score__level-icon ls-score__level-icon--gold" aria-hidden="true">&#9733;</div>
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
          <div class="ls-score__stat ls-reveal ls-reveal--delay-2">
            <span class="ls-score__stat-value" data-count="50">50</span>
            <span class="ls-score__stat-label">Membres<br>max</span>
          </div>
          <div class="ls-score__stat ls-reveal ls-reveal--delay-3">
            <span class="ls-score__stat-value" data-count="3">3</span>
            <span class="ls-score__stat-label">Langues<br>support&eacute;es</span>
          </div>
        </div>
        <div class="ls-score__quote">
          Votre ponctualit&eacute; devient votre capital.<br>
          <strong>Construisez-le paiement apr&egrave;s paiement.</strong>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="ls-cta" aria-labelledby="cta-title">
  <div class="ls-cta__bg" aria-hidden="true"></div>
  <div class="ls-cta__inner">
    <div>
      <img
        src="{{ asset('images/tontine-une dame-qui manage.jfif') }}"
        alt="Une membre TontineSN"
        class="ls-cta__image"
        width="440" height="480"
        loading="lazy"
      >
    </div>
    <div>
      <h2 id="cta-title" class="ls-cta__title">Votre communaut&eacute; vous attend.</h2>
      <p class="ls-cta__subtitle">Cr&eacute;ez votre premi&egrave;re tontine en moins de 2 minutes.<br><strong>Toujours gratuit.</strong></p>
      <a href="{{ route('auth.register') }}" class="ls-cta__btn">
        Commencer maintenant
        <span class="ls-cta__btn-arrow" aria-hidden="true">&#8594;</span>
      </a>
      <div class="ls-cta__reassurance">
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true">&#10003;</span>
          Aucune carte bancaire requise
        </span>
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true">&#10003;</span>
          Inscription gratuite en 2 minutes
        </span>
        <span class="ls-cta__reassurance-item">
          <span class="ls-cta__check" aria-hidden="true">&#10003;</span>
          Wave &amp; Orange Money int&eacute;gr&eacute;s
        </span>
      </div>
    </div>
  </div>
</section>

</main>

<!-- FOOTER -->
<footer class="ls-footer" role="contentinfo">
  <div class="ls-footer__inner">
    <div class="ls-footer__brand">
      <img src="{{ asset('images/Tontine.png') }}" alt="TontineSN" class="ls-footer__logo" width="36" height="36" loading="lazy">
      <p class="ls-footer__tagline">La tontine num&eacute;rique du S&eacute;n&eacute;gal.</p>
    </div>
  </div>
  <div class="ls-footer__bottom">
    <span>&copy; {{ date('Y') }} TontineSN &mdash; Fait au S&eacute;n&eacute;gal</span>
    <div class="ls-footer__links">
      <a href="mailto:contact@tontinesn.com">Contact</a>
      <a href="{{ route('api.docs') }}">API</a>
      <a href="{{ route('cgu') }}">CGU</a>
      <a href="{{ route('mentions') }}">Mentions l&eacute;gales</a>
      <a href="{{ route('privacy') }}">Confidentialit&eacute;</a>
    </div>
  </div>
</footer>

<script>
(function() {
  'use strict';

  // ── Nav scroll ──
  var nav = document.getElementById('nav');
  var ticking = false;
  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(function() {
        nav.classList.toggle('ls-nav--scrolled', window.scrollY > 40);
        ticking = false;
      });
      ticking = true;
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });

  // ── Burger menu ──
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

  // ── Smooth scroll for anchors ──
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      var id = this.getAttribute('href');
      var target = document.querySelector(id);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── Scroll reveal (Intersection Observer) ──
  var revealObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('ls-reveal--visible');
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.ls-reveal').forEach(function(el) {
    revealObserver.observe(el);
  });

  // ── Animated counters ──
  var counted = false;
  var countObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting && !counted) {
        counted = true;
        var counters = document.querySelectorAll('[data-count]');
        counters.forEach(function(counter) {
          var target = parseInt(counter.getAttribute('data-count'), 10);
          var suffix = counter.textContent.replace(/[\d]/g, '');
          var current = 0;
          var step = Math.max(1, Math.floor(target / 40));
          var timer = setInterval(function() {
            current += step;
            if (current >= target) {
              current = target;
              clearInterval(timer);
            }
            counter.textContent = current + suffix;
          }, 30);
        });
        countObserver.disconnect();
      }
    });
  }, { threshold: 0.3 });

  var statsSection = document.querySelector('.ls-score__stats');
  if (statsSection) countObserver.observe(statsSection);
})();
</script>
</body>
</html>
