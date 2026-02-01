# Schedule Bot

A Telegram bot built with Laravel to help students manage all their school activities in one place. Keep track of homework assignments, events, deadlines, and other academic tasks through an intuitive Telegram interface.

## Features

- **Homework Management**: Create, view, update, and delete homework assignments
- **Event Tracking**: Manage school events, deadlines, and important dates
- **Reminders**: Get notified about upcoming assignments and events
- **Centralized Organization**: All school activities in one convenient location
- **Telegram Integration**: Easy-to-use interface through Telegram bot commands

## Technology Stack

- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+
- **Database**: MariaDB

## Project Structure

```
schedule-bot/
├── app/
│   ├── Http/
│   │   └── Controllers/     # HTTP controllers for webhooks/API
│   ├── Models/              # Eloquent models (User, Homework, Event, etc.)
│   └── Providers/           # Service providers
├── config/                  # Configuration files
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── routes/
│   ├── web.php              # Web routes
│   └── console.php          # Artisan commands
├── resources/
└── tests/                   # Test files
```

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- Composer
- MariaDB (or MySQL/PostgreSQL/SQLite if preferred)
- Telegram Bot Token (get one from [@BotFather](https://t.me/BotFather))

### Installation

1. **Clone the repository** (if applicable) or navigate to the project directory:
   ```bash
   git clone https://github.com/mega-daun/schedule-bot && cd schedule-bot
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Set up environment variables**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your `.env` file** with the following required variables:
   ```env
   TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
   TELEGRAM_WEBHOOK_URL=https://your-domain.com/webhook/telegram
   APP_ENV=local
   APP_DEBUG=true
   DB_CONNECTION=sqlite
   ```

5. **Run database migrations**:
   ```bash
   php artisan migrate
   ```

### Running the Application

**Development mode** (runs server, queue worker, logs, and Vite):
```bash
composer run dev
```

**Or run components separately**:
```bash
# Start Laravel development server
php artisan serve

# Start queue worker (for background jobs)
php artisan queue:work

# Watch logs
php artisan pail
```

## Configuration

### Required Environment Variables

- `TELEGRAM_BOT_TOKEN`: Your Telegram bot token from BotFather
- `TELEGRAM_WEBHOOK_URL`: Public URL where Telegram will send webhook updates
- `APP_KEY`: Application encryption key (generated with `php artisan key:generate`)
- `DB_CONNECTION`: Database driver (sqlite, mysql, pgsql, etc.)

### Optional Environment Variables

- `APP_ENV`: Application environment (local, staging, production)
- `APP_DEBUG`: Enable/disable debug mode
- `LOG_CHANNEL`: Logging channel (stack, single, daily, etc.)

### Setting Up Telegram Webhook

After deploying your application, set the webhook URL:
```bash
php artisan telegram:set-webhook
```

Or manually via Telegram API:
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://your-domain.com/webhook/telegram"
```

## Development

### Running Tests

```bash
composer run test
```

Or directly:
```bash
php artisan test
```

### Code Style

This project uses Laravel Pint for code formatting:
```bash
./vendor/bin/pint
```

## Architecture

For detailed architecture documentation, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Contributing

1. Create a feature branch
2. Make your changes
3. Write or update tests
4. Ensure code style compliance (`./vendor/bin/pint`)
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
