#!/bin/bash

# WayloShare Backend - Ubuntu 22.04 VPS Setup Script
# This script sets up the complete environment for WayloShare backend

set -e

echo "=========================================="
echo "WayloShare Backend - Ubuntu VPS Setup"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root${NC}"
   exit 1
fi

# Step 1: Update System
echo -e "${YELLOW}Step 1: Updating system packages...${NC}"
apt update && apt upgrade -y

# Step 2: Install PHP 8.2 and Extensions
echo -e "${YELLOW}Step 2: Installing PHP 8.2 and extensions...${NC}"
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring \
    php8.2-curl php8.2-xml php8.2-bcmath php8.2-redis \
    php8.2-intl php8.2-opcache

# Step 3: Install Nginx
echo -e "${YELLOW}Step 3: Installing Nginx...${NC}"
apt install -y nginx

# Step 4: Install MySQL
echo -e "${YELLOW}Step 4: Installing MySQL Server...${NC}"
apt install -y mysql-server

# Step 5: Install Redis
echo -e "${YELLOW}Step 5: Installing Redis...${NC}"
apt install -y redis-server

# Step 6: Install Composer
echo -e "${YELLOW}Step 6: Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Step 7: Install Supervisor
echo -e "${YELLOW}Step 7: Installing Supervisor...${NC}"
apt install -y supervisor

# Step 8: Install Certbot for SSL
echo -e "${YELLOW}Step 8: Installing Certbot...${NC}"
apt install -y certbot python3-certbot-nginx

# Step 9: Install Git
echo -e "${YELLOW}Step 9: Installing Git...${NC}"
apt install -y git

# Step 10: Install Monitoring Tools
echo -e "${YELLOW}Step 10: Installing monitoring tools...${NC}"
apt install -y htop iotop nethogs curl wget

# Step 11: Create Application Directory
echo -e "${YELLOW}Step 11: Creating application directory...${NC}"
mkdir -p /var/www/wayloshare
chown -R www-data:www-data /var/www/wayloshare

# Step 12: Configure PHP-FPM
echo -e "${YELLOW}Step 12: Configuring PHP-FPM...${NC}"
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 20M/' /etc/php/8.2/fpm/php.ini

# Restart PHP-FPM
systemctl restart php8.2-fpm

# Step 13: Configure Redis
echo -e "${YELLOW}Step 13: Configuring Redis...${NC}"
sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' /etc/redis/redis.conf
sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

# Enable and start Redis
systemctl enable redis-server
systemctl restart redis-server

# Step 14: Configure MySQL
echo -e "${YELLOW}Step 14: Configuring MySQL...${NC}"
systemctl enable mysql-server
systemctl restart mysql-server

# Step 15: Enable Nginx
echo -e "${YELLOW}Step 15: Enabling Nginx...${NC}"
systemctl enable nginx

# Step 16: Configure Firewall
echo -e "${YELLOW}Step 16: Configuring UFW Firewall...${NC}"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Step 17: Create Log Rotation
echo -e "${YELLOW}Step 17: Setting up log rotation...${NC}"
cat > /etc/logrotate.d/wayloshare << 'EOF'
/var/www/wayloshare/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
EOF

echo -e "${GREEN}=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Clone the repository to /var/www/wayloshare"
echo "2. Run: composer install"
echo "3. Configure .env file"
echo "4. Run: php artisan migrate"
echo "5. Configure Nginx site"
echo "6. Obtain SSL certificate with Certbot"
echo "7. Start queue workers with Supervisor"
echo ""
echo "Services Status:"
systemctl status php8.2-fpm --no-pager
systemctl status nginx --no-pager
systemctl status mysql-server --no-pager
systemctl status redis-server --no-pager
