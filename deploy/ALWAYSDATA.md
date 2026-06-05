# Déploiement TontineSN sur AlwaysData (gratuit, sans carte)

## Étape 1 — Créer le compte

1. Va sur [alwaysdata.com](https://www.alwaysdata.com)
2. Clique sur **Inscription** → **Offre gratuite**
3. Remplis : email, mot de passe, prénom/nom
4. Valide l'email → **Aucune carte bancaire demandée**

## Étape 2 — Créer le site

1. Connecte-toi sur **admin.alwaysdata.com**
2. Menu **Sites > Ajouter un site**
   - **Adresse** : `tontine-sn.alwaysdata.net` (gratuit)
   - **Type** : PHP
   - **Version PHP** : `8.2`
   - **Chemin racine** : `/www/tontine-sn/public`
   - **Commande de déploiement** : `bash /home/<TON_USER>/www/tontine-sn/deploy/deploy-alwaysdata.sh`

## Étape 3 — Activer SSH

1. Menu **Compte > Utilisateurs SSH**
2. Crée un utilisateur SSH avec mot de passe
3. Note le nom d'hôte : `ssh-<TON_USER>.alwaysdata.net`
4. Teste la connexion :

```bash
ssh <TON_USER>@ssh-<TON_USER>.alwaysdata.net
```

## Étape 4 — Créer la base MySQL

1. Menu **Bases de données > Ajouter une base MySQL**
2. Note les infos : nom, utilisateur, mot de passe, serveur

## Étape 5 — Cloner le projet

```bash
# Dans le SSH AlwaysData
cd ~/www
git clone https://github.com/Sow221/tontine-sn-2026.git tontine-sn
cd tontine-sn

# Configure .env
cp .env.example .env
nano .env
```

Configure ces variables dans `.env` :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tontine-sn.alwaysdata.net

DB_CONNECTION=mysql
DB_HOST=mysql-<TON_USER>.alwaysdata.net
DB_PORT=3306
DB_DATABASE=<NOM_BDD>
DB_USERNAME=<USER_BDD>
DB_PASSWORD=<MDP_BDD>

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=file

# Pas de Redis sur AlwaysData
REDIS_HOST=
```

## Étape 6 — Installer les dépendances

```bash
# PHP
composer install --no-dev --optimize-autoloader

# Frontend (node est dispo sur AlwaysData)
npm ci
npm run build

# Clé + migrations
php artisan key:generate
php artisan storage:link

# Lance le déploiement
bash deploy/deploy-alwaysdata.sh
```

## Étape 7 — Configurer le cron (pour les queues)

1. Menu **Tâches planifiées > Ajouter une tâche**
2. **Commande** :
```
cd /home/<TON_USER>/www/tontine-sn && php artisan queue:work --stop-when-empty
```
3. **Fréquence** : `* * * * *` (toutes les minutes)
4. **Actif** : Oui

Ajoute aussi pour les tâches planifiées Laravel :

```
cd /home/<TON_USER>/www/tontine-sn && php artisan schedule:run
```

## Étape 8 — Configurer le domaine (optionnel)

Tu peux utiliser `tontine-sn.alwaysdata.net` gratuitement
ou ajouter ton propre domaine dans **Sites > Domaines**.

---

## Résumé AlwaysData

| Fonction | Dispo ? |
|---|---|
| PHP 8.2 | ✅ |
| MySQL | ✅ |
| SSH | ✅ |
| Cron | ✅ |
| HTTPS gratuit | ✅ |
| Stockage | 100 Mo (suffisant avec --no-dev) |
| Redis | ❌ (cache = file) |
| Supervisor | ❌ (cron = queue) |
| **Carte bancaire** | **❌ AUCUNE** |
