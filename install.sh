#!/bin/bash

#===============================================================================
# LocaGed Document Management System - Installation Script
# Ubuntu 22.04 LTS - Production Deployment
#
# AUDIT: Golden installation script for reproducible deployment
# Usage: sudo bash install.sh
#===============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/locaged"
APP_USER="www-data"
PHP_VERSION="8.2"
MYSQL_ROOT_PASSWORD=""  # Set this or it will prompt

echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║       LocaGed Document Management System - Installer          ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

#-------------------------------------------------------------------------------
# Check if running as root
#-------------------------------------------------------------------------------
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}Error: This script must be run as root (sudo)${NC}"
   exit 1
fi

#-------------------------------------------------------------------------------
# Update system packages
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[1/10] Updating system packages...${NC}"
apt update && apt upgrade -y

#-------------------------------------------------------------------------------
# Install Nginx
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[2/10] Installing Nginx...${NC}"
apt install -y nginx
systemctl enable nginx
systemctl start nginx

#-------------------------------------------------------------------------------
# Install PHP 8.2 + Extensions
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[3/10] Installing PHP ${PHP_VERSION} and extensions...${NC}"
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-imagick \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-soap \
    php${PHP_VERSION}-ldap

# Configure PHP
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/post_max_size = .*/post_max_size = 100M/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/${PHP_VERSION}/fpm/php.ini

systemctl restart php${PHP_VERSION}-fpm

#-------------------------------------------------------------------------------
# Install MySQL
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[4/10] Installing MySQL...${NC}"
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Secure MySQL (optional - uncomment if needed)
# mysql_secure_installation

#-------------------------------------------------------------------------------
# Install Redis
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[5/10] Installing Redis...${NC}"
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Configure Redis for production
sed -i "s/^# maxmemory .*/maxmemory 256mb/" /etc/redis/redis.conf
sed -i "s/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/" /etc/redis/redis.conf
systemctl restart redis-server

#-------------------------------------------------------------------------------
# Install Elasticsearch
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[6/10] Installing Elasticsearch...${NC}"

# Install Java (required for Elasticsearch)
apt install -y openjdk-17-jdk

# Add Elasticsearch repository
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | tee /etc/apt/sources.list.d/elastic-8.x.list
apt update
apt install -y elasticsearch

# Configure Elasticsearch for single-node
cat > /etc/elasticsearch/elasticsearch.yml << 'EOF'
cluster.name: locaged-cluster
node.name: node-1
path.data: /var/lib/elasticsearch
path.logs: /var/log/elasticsearch
network.host: 127.0.0.1
http.port: 9200
discovery.type: single-node
xpack.security.enabled: false
EOF

systemctl enable elasticsearch
systemctl start elasticsearch

#-------------------------------------------------------------------------------
# Install Tesseract OCR
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[7/10] Installing Tesseract OCR...${NC}"
apt install -y tesseract-ocr tesseract-ocr-fra tesseract-ocr-ara tesseract-ocr-eng

# Install additional tools for PDF processing
apt install -y poppler-utils imagemagick ghostscript

# Allow ImageMagick to process PDFs
sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml 2>/dev/null || true

#-------------------------------------------------------------------------------
# Install Supervisor
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[8/10] Installing Supervisor...${NC}"
apt install -y supervisor

# Create Laravel queue worker configuration
cat > /etc/supervisor/conf.d/locaged-worker.conf << EOF
[program:locaged-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${APP_USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

#-------------------------------------------------------------------------------
# Install Composer
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[9/10] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#-------------------------------------------------------------------------------
# Configure Application
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[10/10] Configuring application...${NC}"

# Check if app directory exists
if [ ! -d "${APP_DIR}" ]; then
    echo -e "${RED}Error: Application directory ${APP_DIR} does not exist.${NC}"
    echo -e "${YELLOW}Please clone the repository first:${NC}"
    echo -e "  git clone <repository-url> ${APP_DIR}"
    exit 1
fi

cd ${APP_DIR}

# Copy environment file if not exists
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${YELLOW}Created .env from .env.example - PLEASE CONFIGURE IT!${NC}"
    else
        echo -e "${RED}Error: .env.example not found${NC}"
        exit 1
    fi
fi

# Set permissions
echo "Setting permissions..."
chown -R ${APP_USER}:${APP_USER} ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 775 ${APP_DIR}/storage
chmod -R 775 ${APP_DIR}/bootstrap/cache

# Install PHP dependencies
echo "Installing Composer dependencies..."
sudo -u ${APP_USER} composer install --no-dev --optimize-autoloader

# Generate application key if not set
php artisan key:generate --force

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration for production
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Import to Elasticsearch (if configured)
php artisan scout:import "App\Models\DocumentVersion" 2>/dev/null || true

# Restart services
supervisorctl reread
supervisorctl update
supervisorctl restart all

#-------------------------------------------------------------------------------
# Create Nginx configuration
#-------------------------------------------------------------------------------
echo "Creating Nginx configuration..."
cat > /etc/nginx/sites-available/locaged << EOF
server {
    listen 80;
    listen [::]:80;
    server_name locaged.com www.locaged.com;
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/locaged /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t && systemctl reload nginx

#-------------------------------------------------------------------------------
# Setup Cron
#-------------------------------------------------------------------------------
echo "Setting up Laravel scheduler cron..."
(crontab -l 2>/dev/null | grep -v "artisan schedule:run"; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

#-------------------------------------------------------------------------------
# Final Summary
#-------------------------------------------------------------------------------
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                Installation Complete!                          ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Configure .env file with your settings:"
echo "   nano ${APP_DIR}/.env"
echo ""
echo "2. Set up SSL certificate (recommended):"
echo "   apt install certbot python3-certbot-nginx"
echo "   certbot --nginx -d locaged.com -d www.locaged.com"
echo ""
echo "3. Create MySQL database and user:"
echo "   mysql -u root -p"
echo "   CREATE DATABASE locaged;"
echo "   CREATE USER 'locaged'@'localhost' IDENTIFIED BY 'your_password';"
echo "   GRANT ALL ON locaged.* TO 'locaged'@'localhost';"
echo ""
echo "4. Test email configuration:"
echo "   php artisan test:email admin@example.com"
echo ""
echo "5. Test backup:"
echo "   php artisan backup:run"
echo ""
echo -e "${GREEN}Installation completed successfully!${NC}"
