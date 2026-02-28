# WayloShare Backend - Installation Guide

## Local Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Redis
- Git

### Installation Steps

#### 1. Clone Repository

```bash
git clone https://github.com/your-repo/wayloshare-backend.git
cd wayloshare-backend
```

#### 2. Install Dependencies

```bash
composer install
```

#### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE wayloshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create user
mysql -u root -p -e "CREATE USER 'wayloshare_user'@'localhost' IDENTIFIED BY 'password';"

# Grant privileges
mysql -u root -p -e "GRANT ALL PRIVILEGES ON wayloshare.* TO 'wayloshare_user'@'localhost'; FLUSH PRIVILEGES;"

# Update .env with database credentials
nano .env
```

Set these values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wayloshare
DB_USERNAME=wayloshare_user
DB_PASSWORD=your_password
```

#### 5. Run Migrations

```bash
php artisan migrate
```

#### 6. Create Storage Link

```bash
php artisan storage:link
```

#### 7. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Firebase Setup

1. Create a Firebase project at https://console.firebase.google.com
2. Download service account credentials (JSON file)
3. Place the file at the path specified in `FIREBASE_CREDENTIALS` in `.env`
4. Update `FIREBASE_DATABASE_URL` in `.env`

### Redis Setup (Optional for Development)

```bash
# macOS
brew install redis
brew services start redis

# Ubuntu/Debian
sudo apt install redis-server
sudo systemctl start redis-server

# Windows (using WSL)
sudo apt install redis-server
sudo service redis-server start
```

### Queue Workers (Optional for Development)

```bash
# In a separate terminal
php artisan queue:work
```

## Docker Setup (Alternative)

### Using Docker Compose

```bash
# Copy Docker environment
cp .env.docker .env

# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Create storage link
docker-compose exec app php artisan storage:link
```

Access the API at `http://localhost:8000`

## Composer Packages Included

### Core Framework
- `laravel/framework` - Laravel framework
- `laravel/tinker` - Interactive shell

### Firebase Integration
- `kreait/firebase-php` - Firebase Admin SDK

### Caching & Queues
- `predis/predis` - Redis client

### HTTP Client
- `guzzlehttp/guzzle` - HTTP client

### Development Tools
- `phpunit/phpunit` - Testing framework
- `laravel/pint` - Code style fixer
- `mockery/mockery` - Mocking library
- `fakerphp/faker` - Fake data generator

## Project Structure

```
wayloshare/
├── app/
│   ├── Console/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Providers/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
└── composer.lock
```

## Common Commands

### Artisan Commands

```bash
# Create a new controller
php artisan make:controller Api/V1/UserController

# Create a new model with migration
php artisan make:model User -m

# Create a new migration
php artisan make:migration create_users_table

# Create a new job
php artisan make:job SendNotificationJob

# Create a new event
php artisan make:event NewMessageEvent

# Create a new middleware
php artisan make:middleware VerifyFirebaseToken

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Refresh database
php artisan migrate:refresh

# Seed database
php artisan db:seed

# Clear cache
php artisan cache:clear

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage
```

### Code Quality

```bash
# Format code with Pint
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

## Troubleshooting

### "Class not found" Error

```bash
# Regenerate autoloader
composer dump-autoload
```

### Database Connection Error

```bash
# Check .env database credentials
cat .env | grep DB_

# Test MySQL connection
mysql -u wayloshare_user -p -h 127.0.0.1 wayloshare
```

### Redis Connection Error

```bash
# Check if Redis is running
redis-cli ping

# Start Redis
redis-server
```

### Permission Denied Errors

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Fix ownership
sudo chown -R $USER:$USER storage bootstrap/cache
```

### Port Already in Use

```bash
# Use different port
php artisan serve --port=8001
```

## Next Steps

1. Review the [BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md) for complete API specification
2. Review the [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for production deployment
3. Start implementing feature modules (controllers, models, migrations)
4. Write tests for your endpoints
5. Set up CI/CD pipeline

## Support

For issues or questions:
- Check Laravel documentation: https://laravel.com/docs
- Check Firebase documentation: https://firebase.google.com/docs
- Review the architecture document: [BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md)
