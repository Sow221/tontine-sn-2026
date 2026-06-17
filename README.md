# TontineSN

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)]()
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)]()
[![Tests](https://img.shields.io/badge/tests-134%20passing-brightgreen)]()
[![License](https://img.shields.io/badge/License-MIT-green)]()

**Plateforme digitale de gestion de tontines au Sénégal.**

> Projet académique · UADB · D2A · PROMO 17

---

## Ce que fait TontineSN

TontineSN digitalise le système de tontine traditionnel en y ajoutant des mécanismes financiers modernes : quatre modèles économiques distincts, paiement mobile intégré, score crédit algorithmique, gamification et notifications multicanal.

**4 types de tontines :**
- **Classique** — tirage séquentiel ou aléatoire pondéré par le score crédit
- **Enchères** — le membre qui propose le taux de renonciation le plus élevé reçoit le pot en priorité
- **Épargne forcée** — chaque membre récupère sa propre mise à la clôture
- **Cérémoniale** — collecte ponctuelle pour un événement (mariage, baptême…)

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.2+ · Laravel 12 |
| Authentification | Laravel Sanctum · Socialite (Google OAuth) · 2FA TOTP |
| Frontend | Blade · Bootstrap 5 · Alpine.js · Vite |
| Base de données | SQLite (dev) · MySQL (prod) · Eloquent ORM |
| Paiements | PayTech.sn — Wave · Orange Money · Free Money · Carte · Espèces |
| Notifications | Resend (email) · GreenAPI (WhatsApp) · VAPID Web Push |
| Qualité | PHPUnit 11 · PHPStan / Larastan · Laravel Pint |

---

## Architecture

Pattern **Service Layer** : les contrôleurs routent, les services contiennent la logique métier.

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/        # Pages Blade (Auth, Dashboard, Tontine, Payment, Profile…)
│   │   ├── Api/        # API REST v1 (JSON + Sanctum)
│   │   └── Admin/      # Interface d'administration
│   ├── Middleware/     # RoleMiddleware · SecurityHeaders (CSP/nonce) · VerifyGreenApiWebhook
│   └── Requests/       # FormRequests — validation centralisée
├── Models/             # User · Tontine · Cycle · Transaction · CreditScore · Badge…
├── Policies/           # TontinePolicy
├── Providers/          # AppServiceProvider — ViewComposer, bindings IoC
├── Jobs/               # SendWhatsAppNotification · SendChatNotifications (async)
└── Services/
    ├── DrawService              # Tirage séquentiel, pondéré, enchères, véto
    ├── CycleService             # Génération et clôture des cycles
    ├── PaymentService           # Enregistrement et confirmation des paiements
    ├── PayTechService           # Intégration API PayTech
    ├── NotificationService      # Email + WhatsApp + Web Push
    ├── CreditScoringService     # Score 0-100 (ponctualité, montant, ancienneté, badges)
    ├── GamificationService      # Badges, streaks, leaderboard
    └── WebhookOutboundService   # Webhooks sortants
```

---

## Algorithme de tirage pondéré

Le mode aléatoire pondère chaque tirage par le score crédit du membre :

```
poids = 1 + (score_crédit / 100)

Membre A (score 80) → poids 1.80 → probabilité 41%
Membre B (score 50) → poids 1.50 → probabilité 34%
Membre C (score 20) → poids 1.20 → probabilité 25%
```

Un membre qui paie à temps voit son score augmenter, et donc ses chances de recevoir le pot en priorité. Mécanisme d'incitation comportementale intégré.

## Score crédit algorithmique

```
Score (0-100) =
  Ponctualité des paiements  × 40%
  Montant total cotisé       × 25%
  Ancienneté sur la platform × 20%
  Badges obtenus             × 15%
```

---

## Tests

```bash
php artisan test
# Tests: 134 passed (246 assertions)
```

| Suite | Tests |
|---|---|
| DrawService (tirage, véto, enchères) | 17 |
| TontineService (cycles, paiements) | 30 |
| CreditScoringService | 18 |
| GamificationService | 7 |
| PayTechService | 15 |
| Feature — Auth, Tontines, Paiements, Admin, API, Webhooks | 47 |

---

## Sécurité

- **CSRF** sur tous les formulaires
- **CSP + nonce cryptographique** par requête (SecurityHeaders middleware)
- **Rôles** : `member` / `admin` / `super_admin` (RoleMiddleware)
- **Policies** : TontinePolicy sur toutes les actions sensibles
- **2FA TOTP** optionnel (Google Authenticator)
- **Rate limiting** : login (10/min), register (5/min), paiement (5/min)
- **Signature HMAC** des webhooks PayTech
- **Soft Deletes** — pas de suppression définitive des utilisateurs

---

## Installation

```bash
# 1. Dépendances
composer install
npm install

# 2. Configuration
cp .env.example .env
php artisan key:generate

# 3. Base de données + données de démo
php artisan migrate
php artisan db:seed

# 4. Assets + serveur
npm run build
php artisan serve
```

### Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Super Admin | `admin@tontinesn.test` | `Admin2024!` |
| Membre (Fatou Diallo) | `fatou@tontinesn.test` | `Membre2024!` |
| Membre (Moussa Ndiaye) | `membre@tontinesn.test` | `Membre2024!` |
| Manager (Ibrahima Sow) | `manager@tontinesn.test` | `Membre2024!` |

Le seeder charge **29 utilisateurs**, **11 tontines** (tous types, tous statuts), **~200 transactions**, messages chat, badges et scores crédit.

---

## API REST v1

Documentation interactive : `/api/docs`

```
POST   /api/v1/login
GET    /api/v1/tontines
POST   /api/v1/tontines
POST   /api/v1/tontines/{id}/join
POST   /api/v1/cycles/{id}/pay
GET    /api/v1/credit-score
GET    /api/v1/tontines/{id}/messages
```

Sécurisée par tokens Bearer (Laravel Sanctum).

---

## Métriques

| | |
|---|---|
| Fichiers PHP | 175 |
| Lignes de code | ~13 000 |
| Vues Blade | 78 |
| Routes HTTP | ~140 |
| Tests | 134 |
| Services métier | 8 |
| Méthodes de paiement | 5 |
| Canaux de notification | 3 |
