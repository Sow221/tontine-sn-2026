# 🌿 TontineSN — Application Laravel de Gestion de Tontines

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)]()
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)]()
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)]()
[![License](https://img.shields.io/badge/License-MIT-green)]()
[![Tests](https://img.shields.io/badge/tests-104%20passing-brightgreen)]()

Projet académique · UADB -D2A -PROMO17 · Sénégal

---

## Table des matières

- [🌿 TontineSN — Application Laravel de Gestion de Tontines](#-tontinesn--application-laravel-de-gestion-de-tontines)
  - [Table des matières](#table-des-matières)
  - [Stack technique](#stack-technique)
  - [Installation](#installation)
    - [1. Prérequis](#1-prérequis)
    - [2. Configuration](#2-configuration)
    - [3. Base de données](#3-base-de-données)
    - [4. Lancer le serveur](#4-lancer-le-serveur)
  - [Comptes de test (après seeding)](#comptes-de-test-après-seeding)
    - [Données de démonstration](#données-de-démonstration)
  - [Architecture](#architecture)
  - [Modules implémentés](#modules-implémentés)
  - [Algorithme de crédit scoring](#algorithme-de-crédit-scoring)
  - [Sécurité](#sécurité)
  - [Gamification](#gamification)
  - [Contribuer](#contribuer)

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

| Rôle        | Email                       | Mot de passe   |
|-------------|-----------------------------|----------------|
| Super Admin | admin@tontinesn.test        | Admin2024!     |
| Manager     | manager@tontinesn.test      | Membre2024!    |
| Membre 1    | fatou@tontinesn.test        | Membre2024!    |
| Membre 2    | membre@tontinesn.test       | Membre2024!    |

> Connexion par email/mot de passe (ou Google OAuth). 40 utilisateurs seedés au total.

### Données de démonstration

| Tontine                        | Type            | Statut     |
|--------------------------------|-----------------|------------|
| Tontine Sandaga                | Fixe            | En attente |
| Tontine Famille Diallo         | Fixe            | Active     |
| Tontine Amis Thiès             | Fixe            | Active     |
| Tontine Médina                 | Fixe            | Active     |
| Tontine Dakar Plateau          | Fixe            | Terminée   |
| Tontine Castors Tirage         | Pondéré + Véto  | Active     |
| Tontine Enchères Liberté       | Enchères        | Active     |
| Tontine Enchères Teranga       | Enchères        | Active     |
| Tontine Épargne HLM            | Épargne forcée  | Active     |
| Tontine Épargne Cité           | Épargne forcée  | Active     |
| Cagnotte Mariage Aminata       | Cérémonielle    | Active     |

Stats : **222 transactions**, **43 cycles**, **14 badges**

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/          # AuthController, DashboardController, TontineController, PaymentController, CycleController, ProfileController, HistoriqueController
│   │   └── Admin/        # AdminDashboardController
│   ├── Middleware/       # RoleMiddleware, ActivityLogger
│   └── Requests/         # StoreTontineRequest, UpdateTontineRequest
├── Models/               # User, Tontine, Cycle, Transaction, CreditScore
├── Policies/             # TontinePolicy
├── Services/             # AuthService, TontineService, PayTechService, CreditScoringService, NotificationService
└── Jobs/                 # ProcessCycle, SendReminders
```

---

## Modules implémentés

| Phase | Module                              | Statut |
|-------|-------------------------------------|--------|
| 1     | Configuration & Migrations          | ✅     |
| 2     | Authentification email/mdp + Google | ✅     |
| 2     | Réinitialisation mot de passe       | ✅     |
| 3     | Tontines, Cycles, Membres           | ✅     |
| 3     | Approbation des membres             | ✅     |
| 4     | Paiements PayTech.sn                | ✅     |
| 5     | Crédit Scoring (algorithme CDC)     | ✅     |
| 5     | Notifications Push (FCM)            | ✅     |
| 6     | Dashboard, Vues Blade               | ✅     |
| 6     | Profil utilisateur & Historique     | ✅     |
| 6     | Admin Panel (KYC, logs, users)      | ✅     |
| 6     | Tests Feature (10 tests)            | ✅     |

---

## Algorithme de crédit scoring

```
Score (/10) = (total_contribué / 100 000) × 0.3
            + (paiements_à_temps / total_cycles) × 0.5
            + (ancienneté_mois / 12) × 0.2

Badges : Bronze ≥ 4.0 | Argent ≥ 6.5 | Or ≥ 8.5
```
## Sécurité

- Authentification email/mot de passe + Google OAuth (Laravel Socialite)
- Réinitialisation de mot de passe par email (Laravel Password Broker)
- Guard Google OAuth : utilisateurs sans password protégés contre updatePassword
- Middleware de rôles (member / manager / admin / super_admin)
- Rate limiting (10 req/min sur connexion)
- Vérification webhook PayTech par re-call API
- Guard idempotence sur confirmPayment (double webhook)
- Journalisation des actions sensibles (activity_logs)
- Protection CSRF sur tous les formulaires
- Policies Laravel (TontinePolicy) sur toutes les actions sensibles
- lockForUpdate() sur join() pour éviter les race conditions

---

## Gamification

| Fonctionnalité          | Détails                                               |
|-------------------------|-------------------------------------------------------|
| Badges                  | 10 badges déblocables (bronze, silver, gold)          |
| Séries de paiement      | Suivi des paiements consécutifs à temps               |
| Classement              | Leaderboard global ou par tontine                     |
| Notification badges     | Alertes en temps réel sur le dashboard                |

Déclenché automatiquement à chaque paiement confirmé via `GamificationService`.

---

## Contribuer

1. `git checkout -b feature/ma-branche`
2. `composer install && npm install`
3. `cp .env.example .env && php artisan key:generate`
4. `php artisan migrate --seed`
5. `php artisan test` (vérifier qu'aucune régression)
6. Ouvrir une Pull Request

```bash
# Lancer les tests en parallèle
composer test:parallel
```
