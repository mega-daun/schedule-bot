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
- **Telegram Library**: Nutgram
- **Tests**: Pest

## Project Structure

```
schedule-bot/
├── app/
│   ├── Actions/             # High level logic
│   ├── DataObjects/         # Value objects (Lesson, Schedule, Weekday, etc.)
│   ├── Enums/               # Enums (e.g. UserRole)
│   ├── Exceptions/          # Custom exceptions
│   ├── Helpers/             # Message/keyboard generators, parser service
│   ├── Http/
│   │   └── Middleware/      # Bot command middleware (auth, class checks, etc.)
│   ├── Jobs/                # Queued jobs (e.g. BroadcastToUsers)
│   ├── Models/              # Eloquent models (User, Classroom, Subject, etc.)
│   ├── Providers/           # Service providers
│   ├── Repositories/        # Repository interfaces + implementations
│   └── Telegram/
│       ├── Commands/        # Bot commands (/start, /newclass, etc.)
│       ├── Conversations/   # Bot commands with multiple steps (/newhomework, /newschedule, /showhomework, etc.)
│       ├── Menus/           # Inline keyboard menus
│       └── Messages/        # Message renderers (e.g. HomeworkList)
├── config/                  # Configuration files
├── database/
│   ├── factories/           # Model factories
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── routes/
│   ├── telegram.php         # Telegram bot command routes
│   ├── web.php              # Web routes(webhook setup)
│   └── console.php          # Artisan commands
├── resources/
│   └── views/messages/      # Notification/message templates (Homework, Schedule)
└── tests/
    ├── Feature/             # Feature tests (Class/, Homework/, Schedule/, Subject/)
    └── Unit/                # Unit tests (Actions/, DataObjects/, Repositories/, Views/)
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
   composer setup
   ```

3. **Configure your `.env` file** with the following required variables:
   ```env
   TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
   TELEGRAM_WEBHOOK_URL=https://your-domain.com/webhook/telegram
   APP_ENV=local
   APP_DEBUG=true
   DB_CONNECTION=sqlite
   ```

4. **Run database migrations**:
   ```bash
   php artisan migrate
   ```

### Running the Application

**Development mode** (runs server, queue worker, logs):
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
php artisan nutgram:hook:set
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

## Contributing

1. Create a feature branch
2. Make your changes
3. Write or update tests
4. Ensure code style compliance (`./vendor/bin/pint`)
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
