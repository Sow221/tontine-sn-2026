# TontineSN — Documentation Technique
> Plateforme digitale de gestion de tontines au Sénégal

---

## Table des matières

1. [Présentation du projet](#1-présentation-du-projet)
2. [Stack technique](#2-stack-technique)
3. [Architecture de l'application](#3-architecture-de-lapplication)
4. [Modèle de données](#4-modèle-de-données)
5. [Fonctionnalités détaillées](#5-fonctionnalités-détaillées)
6. [Algorithmes clés](#6-algorithmes-clés)
7. [Sécurité](#7-sécurité)
8. [API REST v1](#8-api-rest-v1)
9. [Système de notifications](#9-système-de-notifications)
10. [Tests & Qualité](#10-tests--qualité)
11. [Métriques du projet](#11-métriques-du-projet)
12. [Installation & Configuration](#12-installation--configuration)
13. [Comptes de démonstration](#13-comptes-de-démonstration)

---

## 1. Présentation du projet

**TontineSN** est une application web de gestion de tontines adaptée au contexte sénégalais. Elle digitalise un système d'épargne communautaire traditionnel en y ajoutant des mécanismes modernes : paiement mobile, score crédit, gamification, notifications multicanal et API ouverte.

### Problème résolu

Les tontines traditionnelles souffrent de trois problèmes : manque de traçabilité des paiements, conflits sur le choix du bénéficiaire, et absence d'historique financier pour les membres. TontineSN résout les trois.

### Types de tontines supportés

| Type | Description |
|---|---|
| **Classique (fixed)** | Chaque membre cotise un montant fixe, un bénéficiaire est désigné par tirage à chaque cycle |
| **Enchères (auction)** | Le membre qui propose le taux de renonciation le plus élevé reçoit le pot en priorité |
| **Épargne forcée (forced_saving)** | Chaque membre épargne individuellement ; à la clôture, chacun récupère sa propre mise |
| **Cérémoniale (ceremonial)** | Collecte ponctuelle pour un événement (mariage, baptême…) avec compte à rebours |

---

## 2. Stack technique

### Backend

| Technologie | Version | Rôle |
|---|---|---|
| **PHP** | 8.2+ | Langage serveur |
| **Laravel** | 12 | Framework MVC — routing, ORM, middleware, jobs, events |
| **Laravel Sanctum** | 4.3 | Authentification API par tokens Bearer |
| **Laravel Socialite** | 5.27 | Connexion OAuth Google |
| **SQLite / MySQL** | — | Base de données (SQLite en dev, MySQL en production) |
| **minishlink/web-push** | 10.1 | Notifications push navigateur (protocole VAPID) |
| **dompdf/dompdf** | 3.0 | Génération de reçus de paiement en PDF |
| **intervention/image** | 2.7 | Traitement et validation des documents KYC |
| **resend/resend-laravel** | 1.3 | Envoi d'emails transactionnels |
| **predis/predis** | 3.0 | Client Redis (cache et sessions en production) |

### Frontend

| Technologie | Version | Rôle |
|---|---|---|
| **Bootstrap** | 5 | Grille responsive, composants UI, utilitaires CSS |
| **Alpine.js** | 3 | Réactivité côté client (sidebar, modals, dark mode, états de formulaire) |
| **Vite** | 7 | Bundler et build tool des assets |
| **Font Awesome** | 6 | Bibliothèque d'icônes |
| **CSS Variables** | — | Thème dynamique (light/dark mode, sidebar collapse) |

### Services externes intégrés

| Service | Usage |
|---|---|
| **PayTech** | Agrégateur de paiement sénégalais — Wave, Orange Money, Free Money, Carte bancaire |
| **GreenAPI** | Envoi de messages WhatsApp via API |
| **Google OAuth** | Authentification sociale |
| **Resend** | Emails transactionnels (confirmation, rappels, notifications) |

### Outils de développement

| Outil | Rôle |
|---|---|
| **PHPUnit 11** | Tests unitaires et fonctionnels |
| **PHPStan / Larastan** | Analyse statique du code PHP |
| **Laravel Pint** | Formatage automatique du code (PSR-12) |
| **Laravel Sail** | Environnement Docker pour le développement |
| **Laravel Pail** | Visualisation des logs en temps réel |

---

## 3. Architecture de l'application

### Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────┐
│                        NAVIGATEUR                           │
│           Bootstrap 5 + Alpine.js + Vite                    │
└─────────────────────┬───────────────────────────────────────┘
                      │ HTTP / HTTPS
┌─────────────────────▼───────────────────────────────────────┐
│                   LARAVEL 12 (MVC)                          │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  Routes Web  │  │  Routes API  │  │ Webhooks         │  │
│  │  (Blade)     │  │  (JSON)      │  │ (PayTech,GreenAPI│  │
│  └──────┬───────┘  └──────┬───────┘  └────────┬─────────┘  │
│         │                 │                    │            │
│  ┌──────▼─────────────────▼────────────────────▼─────────┐  │
│  │              CONTROLLERS (Web + API + Admin)           │  │
│  └──────────────────────────┬────────────────────────────┘  │
│                             │                               │
│  ┌──────────────────────────▼────────────────────────────┐  │
│  │                    SERVICE LAYER                       │  │
│  │  DrawService  CycleService  PaymentService             │  │
│  │  NotificationService  CreditScoringService             │  │
│  │  GamificationService  PayTechService                   │  │
│  └──────────────────────────┬────────────────────────────┘  │
│                             │                               │
│  ┌──────────────────────────▼────────────────────────────┐  │
│  │              MODÈLES ELOQUENT (ORM)                    │  │
│  │  User  Tontine  Cycle  Transaction  NotificationLog    │  │
│  │  FcmToken  AuctionBid  CycleVeto  Badge  CreditScore   │  │
│  └──────────────────────────┬────────────────────────────┘  │
│                             │                               │
│  ┌──────────────────────────▼────────────────────────────┐  │
│  │            BASE DE DONNÉES (SQLite / MySQL)            │  │
│  └────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Pattern architectural : Service Layer

Les contrôleurs sont délibérément minces — ils délèguent toute la logique métier aux services injectés par le conteneur IoC de Laravel :

```
Controller → Service → Model → Database
```

Avantages : testabilité (les services sont testés indépendamment), réutilisabilité (un service peut être appelé depuis un contrôleur Web, API, ou une commande Artisan).

### Structure des dossiers

```
app/
├── Console/Commands/       # ProcessOverdueCycles (Artisan scheduler)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # AdminDashboard, AdminUser, AdminTontine…
│   │   ├── Api/            # API v1 (JSON)
│   │   └── Web/            # Pages Blade
│   ├── Middleware/         # RoleMiddleware, SecurityHeaders, VerifyGreenApiWebhook
│   └── Requests/           # FormRequests (validation)
├── Jobs/                   # SendWhatsAppNotification, SendChatNotifications
├── Models/                 # Eloquent models
├── Policies/               # TontinePolicy (Gate)
├── Providers/              # AppServiceProvider (ViewComposer, bindings)
└── Services/               # Logique métier
```

---

## 4. Modèle de données

### Entités principales

```
users
├── id, name, email, phone_number, password
├── role (member | admin | super_admin)
├── kyc_status (none | pending | approved | rejected)
├── payment_streak, max_streak
├── referred_by → users.id
└── soft_deletes

tontines
├── id, name, code (unique, 8 chars), description
├── type (fixed | auction | forced_saving | ceremonial)
├── frequency (daily | weekly | monthly)
├── amount, max_members, quorum, penalty_rate
├── draw_method (sequential | random), weighted_draw, veto_threshold
├── status (pending | active | completed | suspended)
├── created_by → users.id
└── start_date, end_date

tontine_members (pivot)
├── tontine_id, user_id
├── status (pending | active | excluded)
├── position, joined_at

cycles
├── id, tontine_id, cycle_number
├── beneficiary_id → users.id
├── due_date, status (pending | partial | paid | overdue)
├── total_collected, draw_hash, drawn_at

transactions
├── id, cycle_id, user_id
├── amount, method (wave | orange_money | free_money | card | cash)
├── type (cycle_payment | qr_p2p)
├── status (pending | success | failed | reversed)
├── paid_at, failure_reason, metadata

notification_logs
├── id, user_id, channel (email | whatsapp | push)
├── event, message, status (sent | failed | pending)

credit_scores
├── user_id, score (0-100)
├── details (JSON : composantes du score)

fcm_tokens
├── user_id, endpoint, p256dh, auth (Web Push VAPID)

auction_bids
├── cycle_id, user_id, bid_rate (%)

cycle_vetos
├── cycle_id, user_id

badges, user_badges
├── slug, name, description, icon
```

---

## 5. Fonctionnalités détaillées

### Gestion des tontines
- Création avec code d'invitation unique (8 caractères)
- Activation manuelle par le créateur (passe `pending` → `active`)
- Transfert de propriété à un autre membre
- Suspension / réactivation (admin uniquement)
- Lien d'invitation partageable + image Open Graph dynamique
- Exclusion de membre avec notification

### Cycle de cotisation
- Génération automatique des cycles à l'activation
- Calcul de la progression du cycle en temps réel (`completionRate()`)
- Pénalité de retard configurable (% du montant)
- Tirage du bénéficiaire (3 modes : voir §6)
- Véto démocratique sur le tirage
- Clôture forcée par l'admin si blocage

### Paiements
- 5 méthodes : Wave, Orange Money, Free Money, Carte bancaire, Espèces
- Redirection vers PayTech (Wave/OM/Carte) → confirmation par webhook
- Espèces : workflow de validation par le créateur de la tontine
- Annulation dans les 24h (pour les paiements non encore confirmés)
- Reçu PDF téléchargeable
- Contestation d'un paiement espèces

### Score crédit algorithmique
```
Score (0-100) =
  Ponctualité des paiements  × 40%
  Montant total cotisé       × 25%
  Ancienneté (mois)          × 20%
  Badges obtenus             × 15%
```

### Gamification
- Badges : Premier paiement, Streak 3/5/10/20, Montant cumulé…
- Leaderboard des membres par score
- Streak (série de paiements à temps) affiché sur le profil

### KYC (Know Your Customer)
- Upload de pièce d'identité (image)
- File de validation manuelle par l'admin
- Statuts : none / pending / approved / rejected
- Impact sur les fonctionnalités disponibles

### Chat intégré
- Messagerie par tontine (SSE — Server-Sent Events)
- Indicateur de frappe en temps réel
- Notifications WhatsApp sur nouveau message

### Paiement QR P2P
- Génération d'un QR code signé par token
- Scan → confirmation → transaction enregistrée
- Expiration automatique du token

---

## 6. Algorithmes clés

### Tirage du bénéficiaire (`DrawService`)

**Mode séquentiel** : le membre avec la position la plus basse qui n'a pas encore reçu le pot.

**Mode aléatoire pondéré** :
```
Pour chaque membre éligible :
  poids = 1 + (score_crédit / 100)
  Plus le score est élevé, plus la probabilité de tirage est grande.
  Un membre avec score 80 a 1.8x plus de chances qu'un membre avec score 0.
```

**Mode enchères** :
```
1. Chaque membre soumet un taux de renonciation (ex: 12%)
2. Le plus haut taux gagne le tirage
3. Le gagnant reçoit : pot × (1 - taux_gagnant)
4. La différence est redistribuée proportionnellement aux autres membres
```

### Système de véto

Si `veto_threshold` est configuré (ex: 33%) :
- Chaque membre actif peut voter le véto sur un tirage
- Si `votes_véto / membres_actifs × 100 >= seuil` → tirage annulé, nouveau tirage automatique (excluant l'ancien bénéficiaire)

### Détection de cycles overdue (`ProcessOverdueCycles`)

Commande Artisan schedulée toutes les heures :
```
Pour chaque cycle dont due_date < now() ET status != 'paid' :
  Si aucun paiement → status = 'overdue'
  Si paiements partiels → status = 'partial' ou 'overdue'
  Notifier les membres non payeurs
```

---

## 7. Sécurité

### Protection des formulaires et requêtes

| Menace | Protection |
|---|---|
| CSRF | Token Laravel sur chaque formulaire POST/PUT/DELETE |
| XSS | Échappement automatique Blade `{{ }}`, nonce CSP par requête |
| SQL Injection | Eloquent ORM (requêtes paramétrées), jamais de SQL brut |
| Clickjacking | `X-Frame-Options: SAMEORIGIN` |
| Brute force | Rate limiting (throttle middleware) sur login, register, paiement |
| Injection de dépendances | Validation stricte via FormRequests |

### Content Security Policy (CSP)

Le middleware `SecurityHeaders` génère un **nonce cryptographique aléatoire** à chaque requête HTTP. Ce nonce est injecté dans tous les `<script>` inline. Tout script sans nonce valide est bloqué par le navigateur.

```php
// Génération du nonce (SecurityHeaders.php)
$nonce = base64_encode(random_bytes(16));
$request->attributes->set('csp_nonce', $nonce);

// En-tête envoyé au navigateur
Content-Security-Policy: script-src 'self' 'nonce-{nonce}'; ...
```

### Authentification & Autorisations

- **Sessions** (web) + **tokens Sanctum** (API)
- **Rôles** : `member`, `admin`, `super_admin` — appliqués par `RoleMiddleware`
- **Policies** : `TontinePolicy` — contrôle granulaire sur qui peut modifier/supprimer/activer une tontine
- **2FA TOTP** : optionnel, compatible avec Google Authenticator
- **Email vérification** obligatoire avant accès aux fonctionnalités
- **Soft Deletes** sur les utilisateurs (pas de suppression définitive)

### Sécurité des webhooks

Le webhook GreenAPI vérifie un token partagé dans l'URL. Le webhook PayTech vérifie une signature HMAC-SHA256.

---

## 8. API REST v1

L'API est protégée par **Laravel Sanctum** (tokens Bearer). Elle expose les mêmes données que l'interface web, permettant à des applications mobiles ou tierces de s'intégrer.

### Authentification

```
POST   /api/v1/login          → Connexion, retourne un token
POST   /api/v1/register       → Inscription
POST   /api/v1/logout         → Révocation du token
GET    /api/v1/user           → Profil de l'utilisateur connecté
```

### Tontines

```
GET    /api/v1/tontines              → Liste des tontines du membre
POST   /api/v1/tontines              → Créer une tontine
GET    /api/v1/tontines/{id}         → Détail d'une tontine
PUT    /api/v1/tontines/{id}         → Modifier
DELETE /api/v1/tontines/{id}         → Supprimer
POST   /api/v1/tontines/{id}/join    → Rejoindre
POST   /api/v1/tontines/{id}/activate
GET    /api/v1/tontines/{id}/members
POST   /api/v1/tontines/{id}/members/{user}/approve
```

### Cycles & Paiements

```
GET    /api/v1/tontines/{id}/cycles
POST   /api/v1/cycles/{id}/pay       → Initier un paiement
GET    /api/v1/transactions           → Historique
```

### Chat

```
GET    /api/v1/tontines/{id}/messages
POST   /api/v1/tontines/{id}/messages
```

### Score crédit

```
GET    /api/v1/credit-score          → Score et détails du calcul
```

La documentation interactive de l'API est accessible sur `/api/docs` (admin) et `/api/spec` (spec JSON).

---

## 9. Système de notifications

### Architecture multicanal

```
NotificationService
├── sendEmail()        → Resend API (HTML structuré)
├── sendWhatsApp()     → GreenAPI (message texte + lien)
│   └── dispatché via Job asynchrone (queue)
└── sendWebPush()      → VAPID (protocole W3C)
    └── pour chaque endpoint enregistré (fcm_tokens)
```

### Événements notifiés

| Événement | Email | WhatsApp | Web Push |
|---|---|---|---|
| Désignation bénéficiaire | ✓ | ✓ | ✓ |
| Paiement confirmé | ✓ | ✓ | ✓ |
| Adhésion approuvée | ✓ | ✓ | ✓ |
| Rappel de cotisation | ✓ | ✓ | ✓ |
| Nouveau cycle démarré | ✓ | ✓ | ✓ |
| Demande d'adhésion (créateur) | ✓ | ✓ | ✓ |
| KYC approuvé / refusé | ✓ | ✓ | — |
| Nouveau filleul | ✓ | ✓ | — |

### Préférences utilisateur

Chaque membre peut désactiver individuellement chaque canal pour chaque type d'événement depuis ses paramètres de notifications (`/profil/notifications`).

---

## 10. Tests & Qualité

### Couverture des tests

```
php artisan test → 134 tests passent (246 assertions)
```

| Suite | Fichiers | Tests |
|---|---|---|
| Unit — DrawService | DrawServiceTest | 17 |
| Unit — TontineService | TontineServiceTest | 30 |
| Unit — CreditScoringService | CreditScoringServiceTest | 18 |
| Unit — GamificationService | GamificationServiceTest | 7 |
| Unit — PayTechService | PayTechServiceTest | 15 |
| Feature — Auth | AuthControllerTest | 6 |
| Feature — Tontines | TontineTest | 8 |
| Feature — Dashboard | DashboardControllerTest | 2 |
| Feature — Paiements | PaymentControllerTest | 2 |
| Feature — Admin | AdminDashboardTest | 3 |
| Feature — API | ApiTest | 5 |
| Feature — Webhooks | WebhookTest | 3 |
| Feature — Routes publiques | PublicRoutesTest | 8 |

### Qualité du code

- **PHPStan / Larastan** — analyse statique niveau 6 (détection d'erreurs de type, méthodes inexistantes…)
- **Laravel Pint** — formatage automatique PSR-12
- **Pas de SQL brut** — 100% Eloquent ORM
- **FormRequests** — validation centralisée, jamais dans les contrôleurs
- **Service Layer** — logique métier isolée, testable indépendamment

---

## 11. Métriques du projet

| Indicateur | Valeur |
|---|---|
| Fichiers PHP | 175 |
| Lignes de code PHP | ~13 000 |
| Vues Blade | 78 |
| Routes HTTP | ~140 |
| Tests | 134 (246 assertions) |
| Migrations | 1 (squashée) + 4 patches |
| Services métier | 8 |
| Types de tontines | 4 |
| Méthodes de paiement | 5 |
| Canaux de notification | 3 |

---

## 12. Installation & Configuration

### Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (dev) ou MySQL 8+ (production)

### Installation

```bash
# Cloner le projet
git clone <url-du-repo>
cd tontine-sn

# Installer les dépendances PHP et générer la clé
composer install
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate
php artisan db:seed       # Charge 29 utilisateurs, 11 tontines, données démo

# Assets frontend
npm install
npm run build
```

### Variables d'environnement clés

```env
# Application
APP_NAME=TontineSN
APP_ENV=production
APP_URL=https://votre-domaine.com

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tontinesn
DB_USERNAME=user
DB_PASSWORD=secret

# Email (Resend)
RESEND_API_KEY=re_xxxx
MAIL_FROM_ADDRESS=no-reply@tontinesn.com

# Paiement (PayTech)
PAYTECH_API_KEY=xxxx
PAYTECH_API_SECRET=xxxx

# WhatsApp (GreenAPI)
GREENAPI_INSTANCE_ID=xxxx
GREENAPI_TOKEN=xxxx
GREENAPI_WEBHOOK_SECRET=xxxx

# Notifications Push (VAPID)
VAPID_PUBLIC_KEY=xxxx
VAPID_PRIVATE_KEY=xxxx
VAPID_SUBJECT=mailto:contact@tontinesn.com

# Google OAuth
GOOGLE_CLIENT_ID=xxxx
GOOGLE_CLIENT_SECRET=xxxx
```

---

## 13. Comptes de démonstration

Après `php artisan db:seed` :

| Rôle | Email | Mot de passe |
|---|---|---|
| Super Admin | `admin@tontinesn.test` | `Admin2024!` |
| Membre (Fatou Diallo) | `fatou@tontinesn.test` | `Membre2024!` |
| Membre (Moussa Ndiaye) | `membre@tontinesn.test` | `Membre2024!` |
| Manager (Ibrahima Sow) | `manager@tontinesn.test` | `Membre2024!` |

### Données préchargées

| Donnée | Quantité |
|---|---|
| Utilisateurs | 29 (avec KYC variés, parrainages, streaks) |
| Tontines | 11 (tous types, tous statuts) |
| Cycles | ~35 (payés, partiels, overdue, en cours) |
| Transactions | ~200 (Wave, OM, espèces, enchères) |
| Messages chat | 12 (dans 2 tontines) |
| Badges attribués | ~15 (streaks, premiers paiements…) |

### Scénarios de démo recommandés

**1. Vue Admin**
→ `admin@tontinesn.test` → Dashboard (stats globales) → Tontines → "Famille Diallo" (voir qui a payé dans le cycle courant) → Statistiques → Journaux

**2. Vue Membre — Paiement**
→ `fatou@tontinesn.test` → Dashboard → Tontine "Famille Diallo" → Bouton "Payer" (modal sans quitter la page) → Choisir Wave

**3. Vue Membre — Enchères**
→ `fatou@tontinesn.test` → "Enchères Liberté" → Soumettre une enchère → Voir les enchères en cours

**4. Vue Membre — Gamification**
→ `fatou@tontinesn.test` → Dashboard → Section leaderboard → Profil → Badges et score crédit

---

*Document généré le 17 juin 2026 — TontineSN v1.0*
