# 🌿 TontineSN — Application Laravel de Gestion de Tontines

Projet académique · Master Développement Web · Sénégal

---

## Stack technique

| Couche       | Technologie              |
|--------------|--------------------------|
| Backend      | Laravel 12.x / PHP 8.2+  |
| Frontend     | Blade + Bootstrap 5 + Alpine.js |
| Base données | MySQL 8.0+               |
| Cache/Queue  | File (dev) / Redis (prod)|
| Paiements    | Wave API + Orange Money  |
| Notifications| SMS + FCM Push           |

---

## Installation

### 1. Prérequis
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js (optionnel, pour Vite)

### 2. Configuration

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Configurer la base de données dans .env
DB_DATABASE=tontine_sn
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe

# Générer la clé applicative
php artisan key:generate
```

### 3. Base de données

```bash
# Créer la base de données MySQL
mysql -u root -p -e "CREATE DATABASE tontine_sn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Lancer les migrations
php artisan migrate

# Insérer les données de test
php artisan db:seed
```

### 4. Lancer le serveur

```bash
php artisan serve
```

Accès : http://localhost:8000

---

## Comptes de test (après seeding)

| Rôle        | Téléphone        |
|-------------|------------------|
| Super Admin | +221700000001    |
| Gérante     | +221770000002    |
| Membre      | +221780000003    |

> Connexion par OTP SMS (simulé en log en mode dev)

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/          # AuthController, DashboardController, TontineController, PaymentController, CycleController
│   │   ├── Admin/        # AdminDashboardController
│   │   └── UssdController
│   ├── Middleware/       # RoleMiddleware, ActivityLogger
│   └── Requests/         # RegisterRequest, StoreTontineRequest
├── Models/               # User, Tontine, Cycle, Transaction, CreditScore, OtpCode
├── Services/             # AuthService, TontineService, MobileMoneyService, CreditScoringService, NotificationService
└── Jobs/                 # ProcessCycle, SendReminders
```

---

## Modules implémentés

| Phase | Module                          | Statut |
|-------|---------------------------------|--------|
| 1     | Configuration & Migrations      | ✅     |
| 2     | Authentification OTP sans mdp   | ✅     |
| 3     | Tontines, Cycles, Membres       | ✅     |
| 4     | Paiements Wave & Orange Money   | ✅     |
| 5     | Crédit Scoring (algorithme CDC) | ✅     |
| 5     | Notifications SMS/Push          | ✅     |
| 6     | Interface USSD (*144#)          | ✅     |
| 6     | Dashboard, Vues Blade           | ✅     |
| 6     | Admin Panel                     | ✅     |

---

## Algorithme de crédit scoring

```
Score (/10) = (total_contribué / 100 000) × 0.3
            + (paiements_à_temps / total_cycles) × 0.5
            + (ancienneté_mois / 12) × 0.2

Badges : Bronze ≥ 4.0 | Argent ≥ 6.5 | Or ≥ 8.5
```

---

## USSD (*144#)

```
1. Mes tontines
2. Payer cotisation
3. Voir bénéficiaires
4. Historique
5. Mon score crédit
0. Changer langue
```

Endpoint : `POST /ussd`

---

## Sécurité

- Authentification sans mot de passe (OTP 6 chiffres, TTL 5 min)
- Middleware de rôles (member / manager / admin / super_admin)
- Rate limiting (5 req/min sur envoi OTP)
- Vérification signature webhook Wave (HMAC-SHA256)
- Journalisation des actions sensibles (activity_logs)
- Protection CSRF sur tous les formulaires

---

## Langues supportées

- 🇫🇷 Français (`resources/lang/fr/`)
- 🇸🇳 Wolof (`resources/lang/wo/`)
- 🇬🇧 Anglais (`resources/lang/en/`)
