# Online Shop

Multi-vendor e-commerce platform with buyer/seller/admin roles, built with Laravel + Inertia.js + Vue.

## Tech Stack

| Category   | Technology       | Version |
|------------|------------------|---------|
| Backend    | Laravel          | 12.53.0 |
| PHP        | PHP              | 8.3.13  |
| Frontend   | Vue.js           | 3.4.35  |
| SPA Bridge | Inertia.js       | 1.2.0   |
| Auth       | Laravel Jetstream | 5.4.0  |
| Auth Token | Laravel Sanctum  | 4.3.1   |
| Styling    | Tailwind CSS     | 3.4.7   |
| Build Tool | Vite             | 6.4.1   |
| Testing    | Pest             | 3.8.5   |
| Database   | MySQL            | 8       |
| Runtime    | Node.js          | 20.18.1 |

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 20
- MySQL 8

## Installation

```bash
# Clone the repository
git clone <repo-url>
cd online-shop

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed
```

## Development

```bash
# Start Vite dev server (hot reload)
npm run dev

# Start Laravel dev server
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## Build

```bash
npm run build
```

## Testing

```bash
# Run all tests
php artisan test

# Run a single test file
php artisan test tests/Feature/AuthenticationTest.php

# Run a specific test by name
./vendor/bin/pest --filter "test name"
```

## Code Style

```bash
# Fix all PHP files (Laravel Pint)
./vendor/bin/pint

# Fix specific directory
./vendor/bin/pint app/
```

## Project Structure

```
online-shop/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 7 public + 6 seller + 6 admin controllers
│   │   ├── Middleware/         # EnsureRole, HandleInertiaRequests
│   │   └── Policies/           # Product, Order, Shop
│   ├── Models/                 # 8 models + User
│   └── Services/               # Cart, Order, Payment
├── database/
│   └── migrations/             # 14 migrations
├── lang/
│   ├── en/                     # English translations
│   └── zh_TW/                  # Traditional Chinese translations
├── resources/js/
│   ├── Components/             # Shared Vue components
│   ├── Layouts/                # App, Seller, Admin layouts
│   └── Pages/                  # Vue pages (organized by feature)
├── routes/
│   └── web.php                 # All routes (public, auth, seller, admin)
└── tests/                      # Pest tests
```

## License

MIT
