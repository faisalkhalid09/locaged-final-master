#!/bin/bash

#===============================================================================
# LocaGed Document Management System - Disaster Recovery Script
# Restores database and storage from a backup file
#
# AUDIT: Disaster recovery script
# Usage: sudo bash restore.sh /path/to/backup.zip
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
TEMP_DIR="/tmp/locaged-restore-$$"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     LocaGed Document Management System - Disaster Recovery    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

#-------------------------------------------------------------------------------
# Validate arguments
#-------------------------------------------------------------------------------
if [ -z "$1" ]; then
    echo -e "${RED}Error: No backup file specified${NC}"
    echo ""
    echo "Usage: sudo bash restore.sh /path/to/backup.zip"
    echo ""
    echo "Backup files are typically located in:"
    echo "  ${APP_DIR}/storage/app/LocaGed/"
    echo ""
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}Error: Backup file not found: ${BACKUP_FILE}${NC}"
    exit 1
fi

#-------------------------------------------------------------------------------
# Check if running as root
#-------------------------------------------------------------------------------
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}Error: This script must be run as root (sudo)${NC}"
   exit 1
fi

#-------------------------------------------------------------------------------
# Load environment variables
#-------------------------------------------------------------------------------
if [ ! -f "${APP_DIR}/.env" ]; then
    echo -e "${RED}Error: .env file not found at ${APP_DIR}/.env${NC}"
    exit 1
fi

# Source the .env file to get database credentials
export $(grep -v '^#' ${APP_DIR}/.env | xargs)

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-locaged}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

echo -e "${YELLOW}Backup file: ${BACKUP_FILE}${NC}"
echo -e "${YELLOW}Database: ${DB_DATABASE}${NC}"
echo -e "${YELLOW}Application: ${APP_DIR}${NC}"
echo ""

#-------------------------------------------------------------------------------
# Confirmation prompt
#-------------------------------------------------------------------------------
echo -e "${RED}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${RED}║  WARNING: This will OVERWRITE existing data!                   ║${NC}"
echo -e "${RED}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
read -p "Are you sure you want to proceed? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

#-------------------------------------------------------------------------------
# Create temporary directory
#-------------------------------------------------------------------------------
echo ""
echo -e "${YELLOW}[1/5] Extracting backup...${NC}"
mkdir -p "$TEMP_DIR"
unzip -q "$BACKUP_FILE" -d "$TEMP_DIR"

#-------------------------------------------------------------------------------
# Find database dump
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[2/5] Locating database dump...${NC}"
DB_DUMP=$(find "$TEMP_DIR" -name "*.sql" -o -name "*.sql.gz" | head -1)

if [ -z "$DB_DUMP" ]; then
    echo -e "${RED}Error: No database dump found in backup${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

echo "Found: $DB_DUMP"

#-------------------------------------------------------------------------------
# Put application in maintenance mode
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[3/5] Enabling maintenance mode...${NC}"
cd "$APP_DIR"
php artisan down --message="System restore in progress. Please check back soon." --retry=60

#-------------------------------------------------------------------------------
# Restore database
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[4/5] Restoring database...${NC}"

# Check if it's gzipped
if [[ "$DB_DUMP" == *.gz ]]; then
    echo "Decompressing database dump..."
    gunzip -c "$DB_DUMP" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" ${DB_PASSWORD:+-p"$DB_PASSWORD"} "$DB_DATABASE"
else
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" ${DB_PASSWORD:+-p"$DB_PASSWORD"} "$DB_DATABASE" < "$DB_DUMP"
fi

echo "Database restored successfully."

#-------------------------------------------------------------------------------
# Restore storage files
#-------------------------------------------------------------------------------
echo -e "${YELLOW}[5/5] Restoring storage files...${NC}"

# Find storage directory in backup
STORAGE_DIR=$(find "$TEMP_DIR" -type d -name "storage" | head -1)

if [ -n "$STORAGE_DIR" ] && [ -d "$STORAGE_DIR/app" ]; then
    echo "Found storage directory, restoring files..."
    
    # Backup current storage (just in case)
    if [ -d "${APP_DIR}/storage/app" ]; then
        mv "${APP_DIR}/storage/app" "${APP_DIR}/storage/app.backup.$(date +%Y%m%d%H%M%S)"
    fi
    
    # Copy restored storage
    cp -r "$STORAGE_DIR/app" "${APP_DIR}/storage/"
    
    # Fix permissions
    chown -R ${APP_USER}:${APP_USER} "${APP_DIR}/storage/app"
    chmod -R 775 "${APP_DIR}/storage/app"
    
    echo "Storage files restored successfully."
else
    echo -e "${YELLOW}Warning: No storage directory found in backup${NC}"
    echo "Only database was restored."
fi

#-------------------------------------------------------------------------------
# Clear caches and bring application back up
#-------------------------------------------------------------------------------
echo ""
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "Disabling maintenance mode..."
php artisan up

#-------------------------------------------------------------------------------
# Cleanup
#-------------------------------------------------------------------------------
echo "Cleaning up temporary files..."
rm -rf "$TEMP_DIR"

#-------------------------------------------------------------------------------
# Final Summary
#-------------------------------------------------------------------------------
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              Restore Complete!                                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✅ Database restored${NC}"
if [ -n "$STORAGE_DIR" ] && [ -d "$STORAGE_DIR/app" ]; then
    echo -e "${GREEN}✅ Storage files restored${NC}"
fi
echo -e "${GREEN}✅ Caches cleared and rebuilt${NC}"
echo -e "${GREEN}✅ Application is back online${NC}"
echo ""
echo "Please verify the restoration by:"
echo "  1. Logging into the application"
echo "  2. Checking recent documents"
echo "  3. Verifying user accounts"
echo ""
