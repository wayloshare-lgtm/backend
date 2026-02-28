# WayloShare Backend - Quick Start Guide

## 5-Minute Setup (Local Development)

### 1. Clone & Install

```bash
git clone https://github.com/your-repo/wayloshare-backend.git
cd wayloshare-backend
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE wayloshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Update .env with DB credentials
nano .env

# Run migrations
php artisan migrate
```

### 4. Start Server

```bash
php artisan serve
```

API is now running at `http://localhost:8000`

---

## Project Structure Overview

```
app/
├── Http/Controllers/Api/V1/     ← API endpoints
├── Models/                        ← Database models
├── Jobs/                          ← Background jobs
├── Services/                      ← Business logic
└── Middleware/                    ← Request middleware

config/
├── firebase.php                   ← Firebase config
├── cors.php                       ← CORS settings
└── database.php                   ← Database config

database/
├── migrations/                    ← Database schemas
└── seeders/                       ← Sample data

routes/
└── api.php                        ← API routes

storage/
└── logs/                          ← Application logs
```

---

## Key Technologies

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 11.0+ |
| Language | PHP | 8.2+ |
| Database | MySQL | 8.0+ |
| Cache | Redis | Latest |
| Auth | Firebase | Admin SDK 7.0+ |
| HTTP Client | Guzzle | 7.0+ |

---

## Essential Commands

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

---

## Environment Variables

**Critical variables to set:**

```env
APP_KEY=                          # Generate with: php artisan key:generate
DB_PASSWORD=                      # Strong password
FIREBASE_CREDENTIALS=             # Path to Firebase JSON
FIREBASE_DATABASE_URL=            # Firebase database URL
REDIS_HOST=127.0.0.1             # Redis server
REDIS_PORT=6379                  # Redis port
```

---

## API Endpoints Structure

All endpoints follow this pattern:

```
/api/v1/{resource}/{action}
```

Examples:
- `POST /api/v1/auth/verify-firebase-token` - Verify Firebase token
- `GET /api/v1/user/profile` - Get user profile
- `POST /api/v1/rides` - Create a ride
- `GET /api/v1/rides` - List rides
- `POST /api/v1/bookings` - Create booking

---

## Database Tables

Core tables created by migrations:

- `users` - User accounts
- `driver_verifications` - KYC documents
- `vehicles` - User vehicles
- `rides` - Ride listings
- `bookings` - Ride bookings
- `saved_routes` - Saved routes
- `payment_methods` - Payment info
- `chats` - Chat conversations
- `messages` - Chat messages
- `notifications` - User notifications
- `fcm_tokens` - Push notification tokens
- `reviews` - User reviews

---

## Firebase Integration

### Setup Steps

1. Create Firebase project: https://console.firebase.google.com
2. Download service account JSON
3. Place at: `/var/www/wayloshare/firebase-credentials.json`
4. Update `.env`:
   ```env
   FIREBASE_CREDENTIALS=/path/to/credentials.json
   FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
   ```

### Usage

```php
// In controllers
use Kreait\Firebase\Factory;

$factory = (new Factory)->withServiceAccount(config('firebase.credentials.file'));
$auth = $factory->createAuth();

// Verify token
$verifiedIdToken = $auth->verifyIdToken($token);
$uid = $verifiedIdToken->claims()->get('sub');
```

---

## Redis Configuration

Redis is used for:
- **Caching** - Session & query caching
- **Queues** - Background job processing
- **Broadcasting** - Real-time updates
- **Rate Limiting** - API throttling

```bash
# Check Redis status
redis-cli ping

# Monitor Redis
redis-cli monitor

# Clear Redis cache
redis-cli FLUSHALL
```

---

## Queue Workers

Background jobs are processed via Redis queues.

### Start Worker (Development)

```bash
php artisan queue:work
```

### Production Setup (Supervisor)

```bash
# Copy configuration
sudo cp setup/supervisor-wayloshare-worker.conf /etc/supervisor/conf.d/

# Start workers
sudo supervisorctl start wayloshare-worker:*

# Check status
sudo supervisorctl status
```

---

## Logging

Logs are stored in `storage/logs/`

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View queue worker logs
tail -f storage/logs/worker.log

# View Nginx logs (production)
tail -f storage/logs/nginx_access.log
```

---

## Common Issues & Solutions

### "SQLSTATE[HY000]: General error"
```bash
# Regenerate autoloader
composer dump-autoload

# Clear cache
php artisan cache:clear
```

### "Connection refused" (Redis)
```bash
# Start Redis
redis-server

# Or on macOS
brew services start redis
```

### "Class not found"
```bash
# Regenerate autoloader
composer dump-autoload

# Clear config cache
php artisan config:clear
```

### Port 8000 already in use
```bash
# Use different port
php artisan serve --port=8001
```

---

## Deployment Checklist

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

---

## Useful Links

- **Laravel Docs**: https://laravel.com/docs
- **Firebase Admin SDK**: https://firebase.google.com/docs/admin/setup
- **Nginx Docs**: https://nginx.org/en/docs/
- **MySQL Docs**: https://dev.mysql.com/doc/
- **Redis Docs**: https://redis.io/documentation
- **Architecture**: [BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md)
- **Deployment**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- **Installation**: [INSTALLATION.md](INSTALLATION.md)

---

## Next Steps

1. ✅ Foundation setup complete
2. ⏭️ Create database migrations
3. ⏭️ Build API controllers
4. ⏭️ Implement authentication
5. ⏭️ Add business logic
6. ⏭️ Write tests
7. ⏭️ Deploy to production

---

**Ready to build? Start with the [INSTALLATION.md](INSTALLATION.md) guide!**
