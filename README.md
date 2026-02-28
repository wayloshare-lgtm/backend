# WayloShare Backend

A production-ready Laravel REST API for a ride-sharing platform on Ubuntu 22.04 VPS.

## 📚 Documentation Index

### Getting Started
- **[QUICK_START.md](QUICK_START.md)** - 5-minute setup and quick reference
- **[INSTALLATION.md](INSTALLATION.md)** - Local development setup guide
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Production deployment guide

### Architecture & Planning
- **[BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md)** - Complete API specification and architecture
- **[FOUNDATION_SETUP_SUMMARY.md](FOUNDATION_SETUP_SUMMARY.md)** - Overview of foundation setup

### Setup & Configuration
- **[setup/README.md](setup/README.md)** - Setup files documentation
- **[SETUP_VERIFICATION_CHECKLIST.md](SETUP_VERIFICATION_CHECKLIST.md)** - Verification checklist

## 🚀 Quick Start

### Local Development (5 minutes)

```bash
# 1. Clone and install
git clone https://github.com/your-repo/wayloshare-backend.git
cd wayloshare-backend
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Setup database
mysql -u root -p -e "CREATE DATABASE wayloshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Update .env with database credentials
php artisan migrate

# 4. Start server
php artisan serve
```

API is now running at `http://localhost:8000`

### Production Deployment (Ubuntu 22.04)

```bash
# 1. Run automated setup
sudo ./setup/ubuntu-vps-setup.sh

# 2. Clone repository
cd /var/www/wayloshare
git clone https://github.com/your-repo/wayloshare-backend.git .

# 3. Install and configure
composer install --optimize-autoloader --no-dev
cp .env.example .env
php artisan key:generate

# 4. Setup database and services
php artisan migrate --force
sudo cp setup/nginx-wayloshare.conf /etc/nginx/sites-available/wayloshare
sudo ln -s /etc/nginx/sites-available/wayloshare /etc/nginx/sites-enabled/
sudo systemctl restart nginx

# 5. Setup queue workers
sudo cp setup/supervisor-wayloshare-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start wayloshare-worker:*

# 6. Obtain SSL certificate
sudo certbot --nginx -d api.wayloshare.com
```

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for detailed instructions.

## 📦 Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 11.0+ |
| Language | PHP | 8.2+ |
| Web Server | Nginx | Latest |
| Database | MySQL | 8.0+ |
| Cache | Redis | Latest |
| Authentication | Firebase | Admin SDK 7.0+ |
| HTTP Client | Guzzle | 7.0+ |
| Process Manager | Supervisor | Latest |
| SSL | Let's Encrypt | Via Certbot |

## 🏗️ Project Structure

```
wayloshare/
├── app/                          # Application code
│   ├── Http/Controllers/         # API controllers
│   ├── Models/                   # Database models
│   ├── Jobs/                     # Background jobs
│   ├── Services/                 # Business logic
│   └── Middleware/               # Request middleware
├── config/
│   ├── firebase.php             # Firebase configuration
│   └── cors.php                 # CORS settings
├── database/
│   ├── migrations/              # Database schemas
│   └── seeders/                 # Sample data
├── routes/
│   └── api.php                  # API routes
├── storage/
│   └── logs/                    # Application logs
├── setup/
│   ├── ubuntu-vps-setup.sh      # VPS setup script
│   ├── nginx-wayloshare.conf    # Nginx config
│   ├── mysql-setup.sql          # MySQL setup
│   └── supervisor-wayloshare-worker.conf  # Queue workers
├── .env.example                 # Environment template
├── composer.json                # Dependencies
└── DEPLOYMENT_GUIDE.md          # Deployment guide
```

## 🔑 Key Features

### Foundation Setup (✅ Complete)
- ✅ Laravel 11 framework
- ✅ Firebase authentication integration
- ✅ Redis caching and queuing
- ✅ MySQL database
- ✅ Nginx web server
- ✅ Queue worker management
- ✅ Production-ready deployment scripts
- ✅ Comprehensive documentation

### API Endpoints (⏭️ To be implemented)
- Authentication (Firebase token verification)
- User management
- Ride management
- Booking system
- Driver verification (KYC)
- Vehicle management
- Payment methods
- Chat/messaging
- Notifications (FCM)
- Admin endpoints

### Database (⏭️ To be implemented)
- 13 tables with proper relationships
- Indexes for performance
- Soft deletes for data safety
- Audit logging

## 📋 Setup Files

### Automated Setup
- **`setup/ubuntu-vps-setup.sh`** - Installs all required packages and services

### Configuration Files
- **`setup/nginx-wayloshare.conf`** - Nginx web server configuration
- **`setup/mysql-setup.sql`** - MySQL database initialization
- **`setup/supervisor-wayloshare-worker.conf`** - Queue worker configuration

### Laravel Configuration
- **`config/firebase.php`** - Firebase Admin SDK configuration
- **`config/cors.php`** - CORS settings for API

## 🔐 Security Features

- ✅ Security headers (X-Frame-Options, CSP, etc.)
- ✅ CORS configuration for allowed origins
- ✅ Gzip compression
- ✅ Firewall rules (UFW)
- ✅ SSL/TLS support (Let's Encrypt)
- ✅ Environment variable protection
- ✅ Log rotation
- ✅ Proper file permissions
- ✅ Rate limiting ready
- ✅ Input validation ready

## 📊 Infrastructure

### Single Server Setup (Phase 1)
```
┌─────────────────────────────────────┐
│         Ubuntu 22.04 VPS            │
├─────────────────────────────────────┤
│  Nginx (Port 80/443)                │
│  ↓                                  │
│  PHP-FPM (8.2)                      │
│  ↓                                  │
│  Laravel Application                │
├─────────────────────────────────────┤
│  MySQL Database                     │
│  Redis Cache                        │
│  Supervisor (Queue Workers)         │
└─────────────────────────────────────┘
```

## 🎯 Development Phases

### Phase 1: Foundation (✅ COMPLETE)
- ✅ Laravel project initialization
- ✅ Composer packages configuration
- ✅ Ubuntu VPS setup scripts
- ✅ Nginx configuration
- ✅ MySQL setup
- ✅ Redis configuration
- ✅ Queue worker setup
- ✅ Documentation

### Phase 2: Database & Models (⏭️ NEXT)
- Create database migrations
- Define Eloquent models
- Set up relationships
- Add database indexes

### Phase 3: Authentication (⏭️ NEXT)
- Firebase token verification middleware
- User authentication endpoints
- Token refresh mechanism
- User registration flow

### Phase 4: Core Features (⏭️ NEXT)
- User management endpoints
- Ride management system
- Booking system
- Payment integration

### Phase 5: Advanced Features (⏭️ NEXT)
- Chat/messaging system
- Notifications (FCM)
- Driver verification (KYC)
- Reviews and ratings

### Phase 6: Testing & Optimization (⏭️ NEXT)
- Unit tests
- Integration tests
- Performance optimization
- Security hardening

## 🛠️ Common Commands

### Development
```bash
# Create new controller
php artisan make:controller Api/V1/RideController

# Create new model with migration
php artisan make:model Ride -m

# Run migrations
php artisan migrate

# Start queue worker
php artisan queue:work

# Clear all caches
php artisan cache:clear && php artisan config:clear

# Format code
./vendor/bin/pint

# Run tests
php artisan test
```

### Production
```bash
# Install dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache && php artisan route:cache

# Start queue workers
sudo supervisorctl start wayloshare-worker:*

# View logs
tail -f storage/logs/laravel.log
```

## 📖 Documentation Guide

| Document | Purpose | Audience |
|----------|---------|----------|
| [QUICK_START.md](QUICK_START.md) | Quick reference and 5-minute setup | Everyone |
| [INSTALLATION.md](INSTALLATION.md) | Local development setup | Developers |
| [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) | Production deployment | DevOps/Developers |
| [BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md) | Complete API specification | Architects/Developers |
| [setup/README.md](setup/README.md) | Setup files documentation | DevOps |
| [FOUNDATION_SETUP_SUMMARY.md](FOUNDATION_SETUP_SUMMARY.md) | Foundation overview | Project Managers |
| [SETUP_VERIFICATION_CHECKLIST.md](SETUP_VERIFICATION_CHECKLIST.md) | Verification checklist | QA/DevOps |

## 🆘 Troubleshooting

### Common Issues

**"SQLSTATE[HY000]: General error"**
```bash
composer dump-autoload
php artisan cache:clear
```

**"Connection refused" (Redis)**
```bash
redis-server
# or on macOS
brew services start redis
```

**"Class not found"**
```bash
composer dump-autoload
php artisan config:clear
```

**Port 8000 already in use**
```bash
php artisan serve --port=8001
```

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for more troubleshooting.

## 📞 Support

- **Laravel Documentation**: https://laravel.com/docs
- **Firebase Admin SDK**: https://firebase.google.com/docs/admin/setup
- **Nginx Documentation**: https://nginx.org/en/docs/
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **Redis Documentation**: https://redis.io/documentation

## 📝 Environment Variables

### Required
```env
APP_KEY=                          # Generate with: php artisan key:generate
DB_PASSWORD=                      # Strong password
FIREBASE_CREDENTIALS=             # Path to Firebase JSON
FIREBASE_DATABASE_URL=            # Firebase database URL
```

### Optional
```env
MAIL_MAILER=smtp                 # Email configuration
AWS_ACCESS_KEY_ID=               # AWS S3 (for file storage)
AWS_SECRET_ACCESS_KEY=           # AWS S3
AWS_BUCKET=                      # AWS S3
```

See `.env.example` for complete list.

## 🔄 Deployment Checklist

- [ ] Clone repository
- [ ] Run `composer install`
- [ ] Copy `.env.example` to `.env`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure database in `.env`
- [ ] Run migrations: `php artisan migrate`
- [ ] Create storage link: `php artisan storage:link`
- [ ] Set permissions: `chmod -R 775 storage bootstrap/cache`
- [ ] Configure Nginx
- [ ] Obtain SSL certificate
- [ ] Start queue workers
- [ ] Configure cron jobs
- [ ] Test health endpoint

## 📄 License

MIT License - See LICENSE file for details

## 👥 Contributors

- Arush Sharma - Architecture & Design

## 🎉 Getting Started

1. **Read**: Start with [QUICK_START.md](QUICK_START.md)
2. **Setup**: Follow [INSTALLATION.md](INSTALLATION.md) for local development
3. **Deploy**: Use [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for production
4. **Build**: Implement features following [BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md)

---

**Status**: Foundation Setup Complete ✅  
**Version**: 1.0  
**Last Updated**: February 2026

**Ready to build? Start with [QUICK_START.md](QUICK_START.md)!**
