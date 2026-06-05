#!/bin/bash
set -euo pipefail

APP_USER="ubuntu"
APP_DIR="/var/www/tontine-sn"
DB_NAME="tontine_221"
DB_USER="tontine"

echo "=== 1. Mise a jour systeme ==="
sudo apt update && sudo apt upgrade -y

echo "=== 2. Installation paquets ==="
sudo apt install -y nginx mysql-server redis-server supervisor git unzip curl certbot python3-certbot-nginx lsb-release ca-certificates software-properties-common fail2ban

echo "=== 3. Installation PHP 8.2 ==="
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-redis php8.2-gd php8.2-intl php8.2-bcmath php8.2-zip php8.2-mbstring php8.2-xml php8.2-curl php8.2-fileinfo php8.2-bz2

echo "=== 4. Composer ==="
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

echo "=== 5. Node.js 20 ==="
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

echo "=== 6. MySQL ==="
sudo mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "=== 7. PHP config ==="
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 20M/' /etc/php/8.2/fpm/php.ini
sudo systemctl restart php8.2-fpm

echo "=== 8. Nginx ==="
sudo mkdir -p ${APP_DIR}
sudo chown -R ${APP_USER}:${APP_USER} ${APP_DIR}

sudo tee /etc/nginx/sites-available/tontine-sn > /dev/null <<'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/tontine-sn/public;
    index index.php;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
    location ~ /\.ht { deny all; }
}
NGINX

sudo ln -sf /etc/nginx/sites-available/tontine-sn /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

echo "=== 9. Supervisor ==="
sudo tee /etc/supervisor/conf.d/tontine-queue-worker.conf > /dev/null <<'SUPERVISOR'
[program:tontine-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tontine-sn/artisan queue:work database --sleep=3 --tries=3 --timeout=90 --max-jobs=500 --max-time=3600
directory=/var/www/tontine-sn
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/tontine-sn/storage/logs/queue-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=90
SUPERVISOR

sudo supervisorctl reread
sudo supervisorctl update

echo ""
echo "========================================"
echo "  SERVEUR PRET !"
echo "========================================"
echo "Prochaines etapes :"
echo "  cd /var/www/tontine-sn"
echo "  git clone <url-du-repo> ."
echo "  cp .env.example .env"
echo "  php artisan key:generate"
echo "  bash deploy/deploy.sh"
echo "  sudo certbot --nginx"
echo "========================================"
