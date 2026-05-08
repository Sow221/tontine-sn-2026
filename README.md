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
| Paiements    | PayTech.sn (Wave, Orange Money, Free Money) |
| Notifications| FCM Push                 |

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

| Rôle        | Email                          |
|-------------|--------------------------------|
| Super Admin | awas28948@gmail.com            |
| Gérante     | awas28948+manager@gmail.com    |
| Membre      | awas28948+aminata@gmail.com    |

> Connexion par Magic Link email (lien envoyé par mail, simulé en log en mode dev)

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/          # AuthController, DashboardController, TontineController, PaymentController, CycleController
│   │   └── Admin/        # AdminDashboardController
│   ├── Middleware/       # RoleMiddleware, ActivityLogger
│   └── Requests/         # StoreTontineRequest
├── Models/               # User, Tontine, Cycle, Transaction, CreditScore, MagicLink
├── Policies/             # TontinePolicy
├── Services/             # AuthService, TontineService, MobileMoneyService, CreditScoringService, NotificationService
└── Jobs/                 # ProcessCycle, SendReminders
```

---

## Modules implémentés

| Phase | Module                          | Statut |
|-------|---------------------------------|--------|
| 1     | Configuration & Migrations      | ✅     |
| 2     | Authentification Magic Link     | ✅     |
| 3     | Tontines, Cycles, Membres       | ✅     |
| 4     | Paiements Wave & Orange Money   | ✅     |
| 5     | Crédit Scoring (algorithme CDC) | ✅     |
| 5     | Notifications Push (FCM)        | ✅     |
| 6     | Dashboard, Vues Blade           | ✅     |
| 6     | Admin Panel                     | ✅     |
| 6     | Tests Feature (10 tests)        | ✅     |

---

## Algorithme de crédit scoring

```
Score (/10) = (total_contribué / 100 000) × 0.3
            + (paiements_à_temps / total_cycles) × 0.5
            + (ancienneté_mois / 12) × 0.2

Badges : Bronze ≥ 4.0 | Argent ≥ 6.5 | Or ≥ 8.5
```
## Sécurité

- Authentification sans mot de passe (Magic Link email, TTL 15 min, hash SHA-256)
- Middleware de rôles (member / manager / admin / super_admin)
- Rate limiting (5 req/min sur envoi Magic Link)
- Vérification signature webhook Wave (HMAC-SHA256)
- Journalisation des actions sensibles (activity_logs)
- Protection CSRF sur tous les formulaires
- Policies Laravel (TontinePolicy) sur toutes les actions sensibles
