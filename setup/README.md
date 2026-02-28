# WayloShare Backend - Setup Files

This directory contains all configuration and setup files for deploying WayloShare backend on Ubuntu 22.04 VPS.

## Files Overview

### 1. `ubuntu-vps-setup.sh`
**Automated server setup script**

Installs and configures:
- PHP 8.2 with required extensions
- Nginx web server
- MySQL database
- Redis cache server
- Composer package manager
- Supervisor for queue workers
- Certbot for SSL certificates
- Firewall (UFW)
- Log rotation

**Usage:**
```bash
chmod +x ubuntu-vps-setup.sh
sudo ./ubuntu-vps-setup.sh
```

### 2. `nginx-wayloshare.conf`
**Nginx web server configuration**

Features:
- SSL/TLS support (after certificate installation)
- Security headers
- Gzip compression
- PHP-FPM integration
- Static asset caching
- 20MB upload limit for KYC documents

**Installation:**
```bash
sudo cp nginx-wayloshare.conf /etc/nginx/sites-available/wayloshare
sudo ln -s /etc/nginx/sites-available/wayloshare /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 3. `mysql-setup.sql`
**MySQL database initialization script**

Creates:
- `wayloshare` database with UTF-8 support
- `wayloshare_user` database user
- Proper privileges for the user

**Usage:**
```bash
sudo mysql -u root -p < mysql-setup.sql
```

**Important:** Change the password in the script before running!

### 4. `supervisor-wayloshare-worker.conf`
**Supervisor configuration for queue workers**

Configures:
- 4 queue worker processes
- Redis queue connection
- Automatic restart on failure
- Logging to `/var/www/wayloshare/storage/logs/worker.log`

**Installation:**
```bash
sudo cp supervisor-wayloshare-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wayloshare-worker:*
```

## Deployment Workflow

### Step 1: Prepare VPS
```bash
# SSH into your VPS
ssh root@your_vps_ip

# Run automated setup
cd /tmp
wget https://raw.githubusercontent.com/your-repo/wayloshare-backend/main/setup/ubuntu-vps-setup.sh
chmod +x ubuntu-vps-setup.sh
./ubuntu-vps-setup.sh
```

### Step 2: Clone Repository
```bash
cd /var/www/wayloshare
git clone https://github.com/your-repo/wayloshare-backend.git .
```

### Step 3: Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### Step 4: Configure Environment
```bash
cp .env.example .env
nano .env
# Set: APP_KEY, DB_PASSWORD, FIREBASE_CREDENTIALS, etc.
php artisan key:generate
```

### Step 5: Setup Database
```bash
sudo mysql -u root -p < setup/mysql-setup.sql
php artisan migrate --force
```

### Step 6: Configure Web Server
```bash
sudo cp setup/nginx-wayloshare.conf /etc/nginx/sites-available/wayloshare
sudo ln -s /etc/nginx/sites-available/wayloshare /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 7: Setup SSL Certificate
```bash
sudo certbot --nginx -d api.wayloshare.com
```

### Step 8: Configure Queue Workers
```bash
sudo cp setup/supervisor-wayloshare-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wayloshare-worker:*
```

### Step 9: Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/wayloshare
sudo chmod -R 775 /var/www/wayloshare/storage
sudo chmod -R 775 /var/www/wayloshare/bootstrap/cache
sudo chmod 600 /var/www/wayloshare/.env
```

### Step 10: Verify Installation
```bash
# Check API health
curl https://api.wayloshare.com/api/v1/health

# Check services
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
sudo systemctl status mysql-server
sudo systemctl status redis-server
sudo supervisorctl status
```

## Configuration Details

### Nginx Configuration
- **Root Directory**: `/var/www/wayloshare/public`
- **PHP-FPM Socket**: `/var/run/php/php8.2-fpm.sock`
- **Max Upload Size**: 20MB (for KYC documents)
- **Gzip Compression**: Enabled
- **Security Headers**: Configured

### MySQL Configuration
- **Database**: `wayloshare`
- **User**: `wayloshare_user`
- **Character Set**: UTF-8 (utf8mb4)
- **Collation**: utf8mb4_unicode_ci

### Redis Configuration
- **Max Memory**: 256MB
- **Eviction Policy**: allkeys-lru
- **Port**: 6379
- **Host**: 127.0.0.1

### Queue Workers
- **Number of Processes**: 4
- **Queue Connection**: Redis
- **Retry Attempts**: 3
- **Max Execution Time**: 3600 seconds
- **Sleep Duration**: 3 seconds

## Troubleshooting

### Setup Script Fails
```bash
# Check if running as root
whoami  # Should output: root

# Check internet connection
ping google.com

# Check disk space
df -h

# Check available memory
free -h
```

### Nginx Won't Start
```bash
# Test configuration
sudo nginx -t

# Check error logs
sudo tail -f /var/log/nginx/error.log

# Check if port 80/443 is in use
sudo lsof -i :80
sudo lsof -i :443
```

### MySQL Connection Failed
```bash
# Check MySQL status
sudo systemctl status mysql-server

# Test connection
mysql -u wayloshare_user -p -h 127.0.0.1 wayloshare

# Check MySQL error log
sudo tail -f /var/log/mysql/error.log
```

### Queue Workers Not Running
```bash
# Check Supervisor status
sudo supervisorctl status

# Check Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Restart workers
sudo supervisorctl restart wayloshare-worker:*
```

### Redis Connection Error
```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

## Security Considerations

1. **Change MySQL Password**: Update the password in `mysql-setup.sql` before running
2. **Firewall Rules**: Only allow necessary ports (22, 80, 443)
3. **SSH Keys**: Use SSH keys instead of passwords
4. **SSL Certificate**: Always use HTTPS in production
5. **Environment Variables**: Keep `.env` file secure (chmod 600)
6. **Regular Updates**: Keep system packages updated

## Monitoring

### Check Service Status
```bash
# All services
sudo systemctl status php8.2-fpm nginx mysql-server redis-server

# Queue workers
sudo supervisorctl status
```

### View Logs
```bash
# Laravel application
tail -f /var/www/wayloshare/storage/logs/laravel.log

# Queue workers
tail -f /var/www/wayloshare/storage/logs/worker.log

# Nginx
tail -f /var/www/wayloshare/storage/logs/nginx_error.log
```

### System Resources
```bash
# CPU and Memory
htop

# Disk I/O
iotop

# Network
nethogs
```

## Backup & Recovery

### Database Backup
```bash
# Manual backup
mysqldump -u wayloshare_user -p wayloshare > backup.sql

# Automated daily backup (add to crontab)
0 2 * * * mysqldump -u wayloshare_user -p'password' wayloshare | gzip > /backups/wayloshare_$(date +\%Y\%m\%d).sql.gz
```

### Restore from Backup
```bash
# Restore database
mysql -u wayloshare_user -p wayloshare < backup.sql
```

## Support

For detailed information, see:
- [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md) - Complete deployment guide
- [INSTALLATION.md](../INSTALLATION.md) - Local development setup
- [QUICK_START.md](../QUICK_START.md) - Quick reference
- [BACKEND_ARCHITECTURE.md](../BACKEND_ARCHITECTURE.md) - Architecture specification
