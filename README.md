# Online Shop

Multi-vendor e-commerce platform with buyer/seller/admin roles, built with Laravel + Inertia.js + Vue.

## Tech Stack

| Category      | Technology        | Version |
|---------------|-------------------|---------|
| Backend       | Laravel           | 12.53.0 |
| PHP           | PHP               | 8.3.13  |
| Frontend      | Vue.js            | 3.4.35  |
| SPA Bridge    | Inertia.js        | 1.2.0   |
| Auth          | Laravel Jetstream | 5.4.0   |
| Auth Token    | Laravel Sanctum   | 4.3.1   |
| Broadcasting  | Laravel Reverb    | 1.10    |
| WebSocket     | Laravel Echo + pusher-js | 2.3 / 8.5 |
| Styling       | Tailwind CSS      | 3.4.7   |
| Build Tool    | Vite              | 6.4.1   |
| Testing       | Pest              | 3.8.5   |
| Database      | MySQL             | 8       |
| Runtime       | Node.js           | 20.18.1 |

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

# Start Vite + Laravel + Reverb in parallel (for real-time chat / notifications)
npm run dev:full
```

Visit `http://localhost:8000` in your browser.

### Real-time features (chat & notifications)

Both the in-app chat and the notification bell rely on Laravel Reverb (WebSocket). For these to work locally:

1. Set the Reverb env vars in `.env` (see `.env.example`: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, plus the matching `VITE_REVERB_*` mirrors)
2. Run `npm run dev:full` (which also starts `php artisan reverb:start`)

Without Reverb, the bell still works via Inertia data — the `database` notification channel keeps writing entries — but new ones won't appear until the next page navigation.

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
├── .claude/                    # AI task records, decisions, implementation records
├── app/
│   ├── Events/                 # MessageSent (chat broadcast)
│   ├── Http/
│   │   ├── Controllers/        # public + utility + seller + admin (incl. NotificationController)
│   │   └── Middleware/         # EnsureRole, SetLocale, HandleInertiaRequests
│   ├── Notifications/          # OrderPaid, OrderStatusChanged, ShopStatusChanged, ... (database + broadcast)
│   ├── Policies/               # Product, Order, Shop
│   ├── Models/                 # 13 models (constants defined on each model)
│   └── Services/               # Cart, Order, Payment, Conversation
├── database/
│   └── migrations/             # 15 migrations
├── lang/
│   ├── en/                     # English translations (incl. notifications.php)
│   └── zh_TW/                  # Traditional Chinese translations
├── resources/js/
│   ├── Components/             # Shared Vue components (incl. NotificationBell)
│   ├── Layouts/                # App, Seller, Admin layouts
│   └── Pages/                  # Vue pages organized by feature (incl. Notifications/)
├── routes/
│   ├── web.php                 # HTTP routes (public, auth, seller, admin)
│   └── channels.php            # Broadcast channel authorization
└── tests/                      # Pest tests
```

## License

MIT
