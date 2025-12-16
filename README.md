# LocaGed Document Management System

A comprehensive document management system built with Laravel 12, featuring OCR processing, Elasticsearch integration, and multi-language support (French, Arabic, English).

## Table of Contents

- [Requirements](#requirements)
- [Quick Installation](#quick-installation)
- [Manual Installation](#manual-installation)
- [Environment Configuration](#environment-configuration)
- [Backup & Recovery](#backup--recovery)
- [Cron & Scheduler](#cron--scheduler)
- [Supervisor Setup](#supervisor-setup)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)

---

## Requirements

| Software | Version | Purpose |
|----------|---------|---------|
| Ubuntu | 22.04 LTS | Operating System |
| PHP | 8.2+ | Application Runtime |
| MySQL | 8.0+ | Database |
| Redis | 6.0+ | Cache & Sessions |
| Nginx | 1.18+ | Web Server |
| Elasticsearch | 8.x | Full-text Search |
| Tesseract | 4.1+ | OCR Processing |
| Supervisor | 4.2+ | Queue Workers |
| Composer | 2.x | PHP Dependencies |

### PHP Extensions Required

```
php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl php8.2-gd 
php8.2-imagick php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath 
php8.2-intl php8.2-soap php8.2-ldap
```

---

## Quick Installation

For a fresh Ubuntu 22.04 server, use the automated installer:

```bash
# Clone the repository
git clone https://github.com/your-org/locaged.git /var/www/locaged
cd /var/www/locaged

# Run the installer (as root)
sudo bash install.sh
```

The installer will:
- Install all required packages
- Configure Nginx, PHP, MySQL, Redis, Elasticsearch
- Set up Supervisor queue workers
- Configure Laravel scheduler cron
- Create production-ready configurations

---

## Manual Installation

### 1. Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis \
    php8.2-curl php8.2-gd php8.2-imagick php8.2-mbstring php8.2-xml \
    php8.2-zip php8.2-bcmath php8.2-intl

# Install other services
sudo apt install -y nginx mysql-server redis-server tesseract-ocr \
    tesseract-ocr-fra tesseract-ocr-ara supervisor poppler-utils
```

### 2. Configure Application

```bash
cd /var/www/locaged

# Copy environment file
cp .env.example .env

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/locaged
sudo chmod -R 755 /var/www/locaged
sudo chmod -R 775 /var/www/locaged/storage
sudo chmod -R 775 /var/www/locaged/bootstrap/cache
```

---

## Environment Configuration

### Required Variables

```env
# Application
APP_NAME=LocaGed
APP_ENV=production
APP_DEBUG=false                    # MUST be false in production
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=locaged
DB_USERNAME=locaged
DB_PASSWORD=your_secure_password

# Session (Redis recommended)
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Cache (Redis recommended)
CACHE_STORE=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Elasticsearch
ELASTICSEARCH_HOST=localhost:9200
SCOUT_DRIVER=Matchish\ScoutElasticSearch\Engines\ElasticSearchEngine

# Email (Configure for production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="LocaGed"

# Backup
BACKUP_DISK=local
BACKUP_NOTIFICATION_EMAIL=admin@your-domain.com
```

### Test Email Configuration

```bash
php artisan test:email admin@your-domain.com
```

---

## Backup & Recovery

### Automated Backups

Backups run automatically at **02:00 daily** via Laravel scheduler.

**Backup includes:**
- Full MySQL database dump
- All files in `storage/app/`
- Configuration files

**Retention policy:**
- 7 daily backups
- 4 weekly backups
- 4 monthly backups

### Manual Backup

```bash
# Run backup manually
php artisan backup:run

# List existing backups
php artisan backup:list

# Monitor backup health
php artisan backup:monitor
```

### Restore from Backup

```bash
# Restore from a backup file
sudo bash restore.sh /var/www/locaged/storage/app/LocaGed/2024-01-15-02-00-00.zip
```

**The restore script will:**
1. Extract the backup archive
2. Put application in maintenance mode
3. Restore database
4. Restore storage files
5. Clear and rebuild caches
6. Bring application back online

---

## Cron & Scheduler

Set up the Laravel scheduler in crontab:

```bash
sudo crontab -e
```

Add this line:

```
* * * * * cd /var/www/locaged && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

| Task | Schedule | Description |
|------|----------|-------------|
| `backup:run` | 02:00 daily | Create backup |
| `backup:clean` | 03:00 daily | Clean old backups |
| `documents:mark-expired` | 00:30 daily | Mark expired documents |
| `documents:check-expired` | 01:10 daily | Create destruction requests |
| Approval reminders | 02:00 daily | Send pending approval reminders |

---

## Supervisor Setup

Supervisor manages the queue workers. Configuration is at:

```
/etc/supervisor/conf.d/locaged-worker.conf
```

### Commands

```bash
# Reload configuration
sudo supervisorctl reread
sudo supervisorctl update

# Restart workers
sudo supervisorctl restart locaged-worker:*

# Check status
sudo supervisorctl status
```

### Worker Configuration

```ini
[program:locaged-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/locaged/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/locaged/storage/logs/worker.log
```

---

## Security Features

This application includes the following security measures:

### Implemented Protections

| Feature | Description |
|---------|-------------|
| **Session Encryption** | All session data is encrypted |
| **Secure Cookies** | HTTPS-only, HttpOnly, SameSite=Lax |
| **Password Policy** | Min 8 chars, mixed case, numbers, symbols |
| **Security Headers** | CSP, HSTS, X-Frame-Options, X-Content-Type-Options |
| **Rate Limiting** | 5 login attempts before lockout |
| **CSRF Protection** | All forms protected |
| **Production Guard** | App won't start if DEBUG=true in production |

### Security Headers

All responses include:
- `Content-Security-Policy`
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Strict-Transport-Security` (HTTPS only)
- `Referrer-Policy: strict-origin-when-cross-origin`

---

## Troubleshooting

### Common Issues

**1. 500 Internal Server Error**
```bash
# Check Laravel logs
tail -f /var/www/locaged/storage/logs/laravel.log

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
```

**2. Queue Jobs Not Processing**
```bash
# Check Supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart locaged-worker:*
```

**3. Elasticsearch Connection Failed**
```bash
# Check Elasticsearch status
sudo systemctl status elasticsearch

# Test connection
curl -X GET "localhost:9200"
```

**4. Redis Connection Refused**
```bash
# Check Redis status
sudo systemctl status redis-server

# Test connection
redis-cli ping
```

**5. Email Not Sending**
```bash
# Test email configuration
php artisan test:email your@email.com

# Check mail driver
grep MAIL_MAILER .env
```

### Log Files

| Log | Location |
|-----|----------|
| Laravel | `storage/logs/laravel.log` |
| Nginx | `/var/log/nginx/error.log` |
| PHP-FPM | `/var/log/php8.2-fpm.log` |
| Supervisor | `/var/www/locaged/storage/logs/worker.log` |

---

## Support

For issues or questions, contact your system administrator.

---

**Version:** 1.0.0  
**Last Updated:** December 2024
