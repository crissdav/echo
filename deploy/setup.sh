#!/usr/bin/env bash
# ==============================================================
#  Echo — instalación en EC2 (Ubuntu Server 24.04 LTS)
#  Uso (conectado por SSH como "ubuntu"):
#     curl -fsSL https://raw.githubusercontent.com/crissdav/echo/main/deploy/setup.sh -o setup.sh
#     bash setup.sh https://github.com/crissdav/echo.git
#  Se puede volver a ejecutar para actualizar (hace git pull).
# ==============================================================
set -euo pipefail

REPO="${1:-https://github.com/crissdav/echo.git}"
APP_DIR="/var/www/echo"
PHP_VER="8.3"

step() { echo -e "\n\033[1;35m==> $*\033[0m"; }

step "1/8 Memoria swap de 1 GB (la t3.micro solo tiene 1 GB de RAM)"
if ! swapon --show | grep -q /swapfile; then
  sudo fallocate -l 1G /swapfile && sudo chmod 600 /swapfile
  sudo mkswap /swapfile && sudo swapon /swapfile
  echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null
fi

step "2/8 Paquetes: Apache, PHP $PHP_VER, extensiones, Git, Composer y cliente MySQL"
sudo apt-get update -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
  apache2 php libapache2-mod-php php-mysql php-gd php-mbstring php-xml php-curl php-zip \
  unzip git composer mysql-client
sudo a2enmod rewrite headers expires >/dev/null

step "3/8 Código desde GitHub → $APP_DIR"
if [ -d "$APP_DIR/.git" ]; then
  sudo git -C "$APP_DIR" pull --ff-only
else
  sudo git clone "$REPO" "$APP_DIR"
fi
sudo chown -R ubuntu:www-data "$APP_DIR"

step "4/8 Dependencias PHP (AWS SDK) con Composer"
cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction

step "5/8 Archivo .env (credenciales fuera del código)"
if [ ! -f .env ]; then
  cp .env.example .env
  echo "   -> Se creó .env a partir de .env.example. EDÍTALO: nano $APP_DIR/.env"
fi
chmod 640 .env
sudo chown ubuntu:www-data .env
mkdir -p storage/uploads
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

step "6/8 Certificado de Amazon RDS para conexión cifrada (TLS)"
sudo curl -fsSL https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem \
  -o /etc/ssl/certs/rds-global-bundle.pem

step "7/8 Ajustes de PHP (subida de imágenes de hasta 8 MB)"
sudo tee /etc/php/$PHP_VER/apache2/conf.d/99-echo.ini >/dev/null <<'INI'
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 60
expose_php = Off
date.timezone = America/Lima
INI

step "8/8 Sitio de Apache"
sudo cp deploy/apache-echo.conf /etc/apache2/sites-available/echo.conf
sudo a2dissite 000-default >/dev/null 2>&1 || true
sudo a2ensite echo >/dev/null
sudo sed -i 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf
sudo apache2ctl configtest
sudo systemctl reload apache2

echo -e "\n\033[1;32m✔ Echo instalado.\033[0m"
echo "  Siguientes pasos:"
echo "   1) nano $APP_DIR/.env      (endpoint de RDS, usuario, clave, bucket S3)"
echo "   2) Crear BD:  mysql -h <endpoint> -u admin -p --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem < database/schema.sql"
echo "   3) Usuario:   mysql -h <endpoint> -u admin -p --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem < database/usuario_app.sql"
echo "   4) Datos demo: php database/seed.php TuClaveSegura   (una sola vez)"
echo "   5) Probar:    curl -s http://localhost/health"
