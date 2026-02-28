# WayloShare Backend - Deployment Guide

## Prerequisites

- Ubuntu 22.04 VPS with root access
- Domain name (e.g., api.wayloshare.com)
- Firebase project credentials
- Basic knowledge of Linux/Ubuntu

## Step-by-Step Deployment

### 1. Initial Server Setup

```bash
# SSH into your VPS
ssh root@your_vps_ip

# Run the automated setup script
cd /tmp
wget https://raw.githubusercontent.com/your-repo/wayloshare-backend/main/setup/ubuntu-vps-setup.sh
chmod +x ubuntu-vps-setup.sh
./ubuntu-vps-setup.sh
```

This script will:
- Update system packages
- Install PHP 8.2 with required extensions
- Install Nginx, MySQL, Redis
- Install Composer, Supervisor, Certbot
- Configure firewall
- Set up log rotation

### 2. Clone Repository

```bash
cd /var/www/wayloshare
git clone https://github.com/your-repo/wayloshare-backend.git .
```

### 3. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### 4. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env with your settings
nano .env
```

**Important .env variables to set:**

```env
APP_KEY=                          # Generate with: php artisan key:generate
DB_PASSWORD=                      # Set strong password
FIREBASE_CREDENTIALS=             # Path to Firebase JSON credentials
FIREBASE_DATABASE_URL=            # Your Firebase database URL
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/wayloshare
sudo chmod -R 775 /var/www/wayloshare/storage
sudo chmod -R 775 /var/www/wayloshare/bootstrap/cache
sudo chmod 600 /var/www/wayloshare/.env
```

### 7. Setup Database

```bash
# Create database and user
sudo mysql -u root -p < setup/mysql-setup.sql

# Run migrations
php artisan migrate --force
```

### 8. Create Storage Link

```bash
php artisan storage:link
```

### 9. Configure Nginx

```bash
# Copy Nginx configuration
sudo cp setup/nginx-wayloshare.conf /etc/nginx/sites-available/wayloshare

# Enable site
sudo ln -s /etc/nginx/sites-available/wayloshare /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 10. Obtain SSL Certificate

```bash
# Get SSL certificate from Let's Encrypt
sudo certbot --nginx -d api.wayloshare.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### 11. Configure Queue Workers

```bash
# Copy Supervisor configuration
sudo cp setup/supervisor-wayloshare-worker.conf /etc/supervisor/conf.d/

# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start wayloshare-worker:*

# Check status
sudo supervisorctl status wayloshare-worker:*
```

### 12. Configure Cron Jobs

```bash
# Edit crontab for www-data user
sudo crontab -e -u www-data
```

Add this line:

```cron
* * * * * cd /var/www/wayloshare && php artisan schedule:run >> /dev/null 2>&1
```

### 13. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 14. Verify Installation

```bash
# Check API health
curl https://api.wayloshare.com/api/v1/health

# Check logs
tail -f /var/www/wayloshare/storage/logs/laravel.log

# Check queue workers
sudo supervisorctl status

# Check Redis
redis-cli ping

# Check MySQL
mysql -u wayloshare_user -p -e "SELECT 1;"
```

## Post-Deployment Checklist

- [ ] Database migrations completed
- [ ] Storage link created
- [ ] Nginx configured and SSL certificate installed
- [ ] Queue workers running
- [ ] Cron jobs configured
- [ ] Firebase credentials uploaded
- [ ] Environment variables configured
- [ ] Logs are being written correctly
- [ ] Health check endpoint responding
- [ ] Firewall rules configured

## Monitoring

### Check Service Status

```bash
# PHP-FPM
sudo systemctl status php8.2-fpm

# Nginx
sudo systemctl status nginx

# MySQL
sudo systemctl status mysql-server

# Redis
sudo systemctl status redis-server

# Supervisor (Queue Workers)
sudo supervisorctl status
```

### View Logs

```bash
# Laravel logs
tail -f /var/www/wayloshare/storage/logs/laravel.log

# Nginx access logs
tail -f /var/www/wayloshare/storage/logs/nginx_access.log

# Nginx error logs
tail -f /var/www/wayloshare/storage/logs/nginx_error.log

# Queue worker logs
tail -f /var/www/wayloshare/storage/logs/worker.log

# MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### System Monitoring

```bash
# CPU and Memory usage
htop

# Disk I/O
iotop

# Network usage
nethogs
```

## Troubleshooting

### 502 Bad Gateway Error

```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check Nginx error logs
tail -f /var/www/wayloshare/storage/logs/nginx_error.log
```

### Database Connection Error

```bash
# Check MySQL status
sudo systemctl status mysql-server

# Test connection
mysql -u wayloshare_user -p -h 127.0.0.1 wayloshare

# Check .env database credentials
cat /var/www/wayloshare/.env | grep DB_
```

### Queue Workers Not Processing

```bash
# Check Supervisor status
sudo supervisorctl status wayloshare-worker:*

# Restart workers
sudo supervisorctl restart wayloshare-worker:*

# Check worker logs
tail -f /var/www/wayloshare/storage/logs/worker.log

# Check Redis connection
redis-cli ping
```

### Redis Connection Error

```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping

# Check Redis configuration
cat /etc/redis/redis.conf | grep maxmemory
```

## Backup Strategy

### Daily Database Backup

```bash
# Create backup script
sudo nano /usr/local/bin/backup-wayloshare.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/wayloshare"
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u wayloshare_user -p'password' wayloshare > $BACKUP_DIR/wayloshare_$DATE.sql
gzip $BACKUP_DIR/wayloshare_$DATE.sql

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/wayloshare_$DATE.sql.gz"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-wayloshare.sh

# Add to crontab
sudo crontab -e
```

Add this line:

```cron
0 2 * * * /usr/local/bin/backup-wayloshare.sh
```

## Security Hardening

### Update System Regularly

```bash
sudo apt update && sudo apt upgrade -y
```

### Configure Firewall

```bash
# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP
sudo ufw allow 80/tcp

# Allow HTTPS
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

### Secure SSH

```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config

# Change default port (optional)
# Port 2222

# Disable root login
PermitRootLogin no

# Disable password authentication (use keys only)
PasswordAuthentication no

# Restart SSH
sudo systemctl restart ssh
```

### Set Up Fail2Ban

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Start Fail2Ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## Scaling Considerations

### Horizontal Scaling

When you need to scale beyond a single server:

1. **Load Balancer**: Set up Nginx or HAProxy to distribute traffic
2. **Database Replication**: Configure MySQL master-slave replication
3. **Redis Cluster**: Set up Redis cluster for distributed caching
4. **Separate Queue Server**: Move queue workers to dedicated server

### Performance Optimization

```bash
# Enable OPcache
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini

# Set these values:
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- Firebase Admin SDK: https://firebase.google.com/docs/admin/setup
- Nginx Documentation: https://nginx.org/en/docs/
- MySQL Documentation: https://dev.mysql.com/doc/
