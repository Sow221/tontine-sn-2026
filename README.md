# Tontine 221 — Plateforme de Tontine Numérique Sénégalaise

Application web Laravel 12 de gestion de tontines avec authentification OTP, intégration Mobile Money (Wave, Orange Money) et support USSD.

## Fonctionnalités

- Authentification sans mot de passe via OTP (SMS)
- Création et gestion de tontines
- Gestion des cycles et tirages
- Paiements Mobile Money (Wave, Orange Money)
- Score de crédit des membres
- Interface USSD pour accès sans smartphone
- Espace admin avec logs d'activité
- Support multilingue (Français / Wolof)

## Stack technique

- **Backend** : Laravel 12 (PHP 8.2+)
- **Base de données** : MySQL / PostgreSQL
- **Frontend** : Blade + CSS personnalisé
- **Queue** : Laravel Jobs (reminders, cycles)
- **Paiements** : Wave API, Orange Money API

## Installation

```bash
git clone https://github.com/Sow221/tontine-221.git
cd tontine-221
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```

## Configuration `.env`

```env
DB_CONNECTION=mysql
DB_DATABASE=tontine_221

WAVE_API_KEY=
WAVE_SECRET=
ORANGE_API_KEY=
ORANGE_SECRET=

SMS_PROVIDER=
SMS_API_KEY=
```

## Structure du projet

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Dashboard admin
│   │   ├── Web/            # Auth, Tontines, Cycles, Paiements
│   │   └── UssdController  # Interface USSD
│   ├── Middleware/         # Auth, RoleMiddleware, ActivityLogger
│   └── Requests/           # Validation
├── Models/                 # User, Tontine, Cycle, Transaction, CreditScore
├── Services/               # AuthService, TontineService, MobileMoneyService...
└── Jobs/                   # ProcessCycle, SendReminders
```

## Licence

MIT
