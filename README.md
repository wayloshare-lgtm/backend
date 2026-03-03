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

### API Endpoints (✅ Complete)
- ✅ Authentication (Firebase token verification + Sanctum API tokens)
- ✅ User management (profile, login, logout, delete account)
- ✅ Ride management (request, accept, arrive, start, complete, cancel)
- ✅ Driver profiles (create, update, location tracking, online status)
- ✅ Fare engine (dynamic pricing, editable via admin)
- ✅ Admin endpoints (fare configuration, fare calculation)
- ✅ Health check endpoint (DB, Redis, Queue status)
- ⏭️ Booking system
- ⏭️ Driver verification (KYC)
- ⏭️ Payment methods
- ⏭️ Chat/messaging
- ⏭️ Notifications (FCM)

### Database (✅ Complete)
- ✅ 4 tables with proper relationships (users, driver_profiles, fare_settings, rides)
- ✅ Indexes for performance (rides, bookings, messages, fcm_tokens)
- ✅ Foreign key constraints with cascade delete
- ✅ Proper data types (decimal for money, enum for status)
- ✅ Timestamps for all tables

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
- ✅ Laravel 11 project initialization with composer
- ✅ Firebase Admin SDK integration (v7.0+)
- ✅ Sanctum authentication package setup
- ✅ Redis and Predis configuration
- ✅ Guzzle HTTP client integration
- ✅ Ubuntu 22.04 VPS setup scripts
- ✅ Nginx configuration with SSL support
- ✅ MySQL 8.0+ database setup
- ✅ Redis cache and queue configuration
- ✅ Supervisor queue worker setup
- ✅ Comprehensive documentation suite

### Phase 2: Database & Models (✅ COMPLETE)
- ✅ Users table migration (firebase_uid, phone, email, role, is_active, is_verified)
- ✅ Driver profiles table (license_number, vehicle_type, vehicle_number, location, online status)
- ✅ Fare settings table (base_fare, per_km_rate, per_minute_rate, surge multiplier, night multiplier)
- ✅ Rides table (pickup/dropoff locations, status enum, fare breakdown, timestamps)
- ✅ Eloquent models with relationships (User, DriverProfile, FareSetting, Ride)
- ✅ Foreign key constraints with cascade delete
- ✅ Production indexes (rides status/created_at, bookings ride_id/status, messages chat_id/created_at)
- ✅ Database seeders (FareSettingSeeder with default pricing)

### Phase 3: Authentication (✅ COMPLETE)
- ✅ Firebase token verification middleware (VerifyFirebaseToken)
- ✅ Sanctum API token generation and management (TokenService)
- ✅ POST /api/v1/auth/login endpoint (Firebase token → Sanctum token exchange)
- ✅ GET /api/v1/auth/me endpoint (current user profile)
- ✅ POST /api/v1/auth/logout endpoint (token revocation)
- ✅ DELETE /api/v1/auth/delete-account endpoint (account deletion)
- ✅ User auto-registration on first Firebase login
- ✅ Role-based access control middleware (CheckDriverRole, CheckAdminRole)
- ✅ Proper API authentication middleware (returns 401 JSON for API requests)

### Phase 4: Core Features (✅ COMPLETE)
- ✅ Ride request endpoint (POST /api/v1/rides with pickup/dropoff coordinates)
- ✅ Ride acceptance endpoint (POST /api/v1/rides/{ride}/accept - driver only)
- ✅ Ride arrival endpoint (POST /api/v1/rides/{ride}/arrive - driver only)
- ✅ Ride start endpoint (POST /api/v1/rides/{ride}/start - driver only)
- ✅ Ride completion endpoint (POST /api/v1/rides/{ride}/complete - driver only)
- ✅ Ride cancellation endpoint (POST /api/v1/rides/{ride}/cancel)
- ✅ Get ride details endpoint (GET /api/v1/rides/{ride})
- ✅ Driver profile endpoints (create, update, location tracking, online status toggle)
- ✅ Dynamic fare calculation engine (FareCalculatorService with full breakdown)
- ✅ Admin fare configuration endpoints (get, create/update, calculate estimate)

### Phase 5: Production Hardening (✅ COMPLETE)
- ✅ Safe conditional updates for all ride state transitions (checks rowsAffected)
- ✅ Custom exception handling (RideAlreadyTakenException, InvalidRideTransitionException, InsufficientSeatsException)
- ✅ Database transaction support for all state changes
- ✅ Comprehensive logging for ride operations and failures
- ✅ Production-grade database indexes and query optimization
- ✅ Redis caching for ride searches (5-minute TTL)
- ✅ Redis caching for user profiles (30-minute TTL)
- ✅ File upload validation service (mime types, size limits, UUID filenames)
- ✅ Health check endpoint (GET /api/v1/health - DB, Redis, Queue status)
- ✅ Throttle middleware for rate limiting (60 requests/minute per user)
- ✅ CORS configuration for API security
- ✅ Production .env configuration (APP_DEBUG=false, QUEUE_CONNECTION=redis)
- ✅ Comprehensive error handling and JSON responses

### Phase 6: Testing & API Documentation (✅ COMPLETE)
- ✅ Postman collection with all 18 API endpoints
- ✅ Automatic Firebase token extraction and reuse
- ✅ Automatic Sanctum token extraction and reuse
- ✅ Automatic ride ID extraction for sequential testing
- ✅ Pre-request scripts for token management
- ✅ Test scripts for response validation
- ✅ Organized endpoint groups (Health, Auth, Rides, Driver, Admin, Cleanup)
- ✅ Complete API endpoint documentation (API_ENDPOINTS.md)

### Phase 7: Advanced Features (⏭️ NEXT)
- Chat/messaging system
- Notifications (FCM)
- Driver verification (KYC)
- Reviews and ratings
- Payment integration
- Booking system
- Real-time tracking (WebSocket/Socket.io)

### Phase 8: Performance & Scaling (⏭️ NEXT)
- Database query optimization
- Caching strategy refinement
- Load testing and benchmarking
- Horizontal scaling setup
- CDN integration
- Advanced monitoring and alerting

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
