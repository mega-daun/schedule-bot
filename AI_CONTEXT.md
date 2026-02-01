# AI Assistant Context - Schedule Bot

This document provides essential context for AI assistants working on the schedule-bot project.

## Project Overview

**Schedule Bot** is a Telegram bot application built with Laravel that helps students manage all their school activities in one centralized location. The bot enables users to track homework assignments, events, deadlines, and other academic tasks through an intuitive Telegram interface.

### Core Functionality
- Homework management (create, view, update, delete, mark complete)
- Event tracking (school events, deadlines, important dates)
- Reminder notifications for upcoming assignments and events
- Centralized organization of all academic activities

## Tech Stack

### Framework & Language
- **Laravel**: 12.x
- **PHP**: 8.2 or higher
- **Code Style**: PSR-12 (enforced via Laravel Pint)

### Database
- **Primary Database**: MariaDB
- **ORM**: Eloquent (Laravel's ORM)
- **Migrations**: All schema changes via Laravel migrations

### External Services
- **Telegram Bot API**: For bot functionality
- **Architecture**: Webhook-based (not polling)

### Development Tools
- **Code Formatter**: Laravel Pint (`./vendor/bin/pint`)
- **Testing**: PHPUnit
- **Queue System**: Laravel Queues (for Telegram API rate limiting)

## Architecture Guidelines

### MVC Pattern
Follow Laravel's Model-View-Controller structure strictly:
- **Models**: Data layer (Eloquent models in `app/Models/`)
- **Controllers**: Request/response handling (in `app/Http/Controllers/`)
- **Services**: Business logic layer (in `app/Services/`)

### Service Layer Pattern
- **Controllers are thin**: They only handle HTTP/webhook requests and responses
- **Business logic in services**: All business logic belongs in service classes
- **Models are thin**: Use Eloquent relationships, avoid business logic in models
- **Single Responsibility**: Each service class handles one entity/domain

### Data Flow
```
Telegram Webhook → Controller → Service → Model → Database
                                    ↓
                              Queue Job → Telegram API
```

## Development Methodology

### Test-Driven Development (TDD)
- **Always write tests first** before implementing features
- Write tests for all new functionality
- Test both happy paths and error cases
- Use PHPUnit for all testing
- Mock external API calls (Telegram API) in tests

### Code Quality Standards
- **Type Hints**: Always use type hints for parameters and return types
- **Strict Types**: Use `declare(strict_types=1);` at the top of PHP files
- **PSR-12**: Follow PSR-12 coding standards (enforced by Laravel Pint)
- **Laravel Conventions**: Follow Laravel naming and structure conventions
- **Validation**: Always validate user inputs using Laravel's validation

### Error Handling
- Handle errors gracefully with proper logging
- Use Laravel's exception handling system
- Return user-friendly error messages
- Never expose stack traces or sensitive data
- Log errors appropriately for debugging

## Telegram Bot Specific Guidelines

### Architecture
- **Webhook-based**: Receive updates via webhook endpoint, not polling
- **Queue all API calls**: Use Laravel queues for all Telegram API calls to respect rate limits
- **Message Formatting**: Use Markdown for message formatting (avoid HTML unless necessary)

### Command Handling
- Parse commands with proper validation
- Handle malformed commands gracefully
- Provide helpful error messages
- Support command aliases where appropriate

### Rate Limiting
- **Always use queues** for Telegram API calls
- Never make direct API calls from controllers
- Create queue jobs for sending messages. This prevents hitting Telegram's rate limits

## Database Conventions

### Migrations
- Use migrations for **all** schema changes
- Follow Laravel migration naming: `YYYY_MM_DD_HHMMSS_description.php`
- Never modify database schema directly
- Always include rollback logic in migrations

### Eloquent ORM
- Use Eloquent for all database operations (never raw SQL)
- Define relationships clearly (hasMany, belongsTo, etc.)
- Use Eloquent scopes for common queries
- Always include `created_at` and `updated_at` timestamps

### Relationships
- User hasMany Homework
- User hasMany Event
- Homework belongsTo User
- Event belongsTo User

## Code Organization

### Directory Structure
```
app/
├── Http/
│   └── Controllers/     # Webhook/API controllers (thin, delegate to services)
├── Services/            # Business logic services
│   ├── HomeworkService.php
│   ├── EventService.php
│   ├── NotificationService.php
│   └── TelegramService.php
├── Models/              # Eloquent models
│   ├── User.php
│   ├── Homework.php
│   └── Event.php
├── Jobs/                # Queue jobs (for Telegram API calls)
├── Providers/          # Service providers
└── Console/            # Artisan commands
```

### Naming Conventions
- **Classes**: PascalCase (e.g., `HomeworkService`, `TelegramController`)
- **Methods**: camelCase (e.g., `createHomework()`, `sendNotification()`)
- **Database Tables**: snake_case, plural (e.g., `homework_assignments`)
- **Database Columns**: snake_case (e.g., `due_date`, `user_id`)
- **Constants**: UPPER_SNAKE_CASE
- **Variables**: camelCase

## When Adding New Features

Follow this step-by-step process:

1. **Write Tests First** (TDD)
   - Create test file in `tests/`
   - Write tests for expected behavior
   - Tests should fail initially (red phase)

2. **Create Database Migration** (if schema changes needed)
   ```bash
   php artisan make:migration create_homework_table
   ```

3. **Create/Update Model**
   - Create model: `php artisan make:model Homework`
   - Define relationships
   - Add fillable/guarded properties
   - Add casts if needed

4. **Create Service Class**
   - Create in `app/Services/HomeworkService.php`
   - Implement business logic
   - Use type hints and return types
   - Keep methods focused and single-purpose

5. **Create/Update Controller**
   - Keep controller thin
   - Validate input
   - Call service methods
   - Format response for Telegram
   - Dispatch queue jobs for API calls

6. **Create Queue Job** (if Telegram API call needed)
   ```bash
   php artisan make:job SendTelegramMessage
   ```
   - Handle Telegram API calls in job
   - Dispatch from controller: `SendTelegramMessage::dispatch($chatId, $message)`

7. **Write Tests**
   - Test service methods
   - Test controller endpoints
   - Test error cases
   - Mock external API calls

8. **Update Documentation**
   - Update README.md if needed
   - Update ARCHITECTURE.md if architecture changes
   - Add PHPDoc comments

## Common Patterns

### Bot Command Pattern
```php
// Controller receives webhook
public function handle(Request $request)
{
    $command = $this->parseCommand($request);
    $this->validateCommand($command);
    
    $result = $this->homeworkService->create($command);
    
    SendTelegramMessage::dispatch($chatId, $this->formatResponse($result));
    
    return response()->json(['ok' => true]);
}
```

### Service Pattern
```php
class HomeworkService
{
    public function create(array $data): Homework
    {
        // Validate input
        // Business logic
        // Create model
        // Return result
    }
}
```

### Queue Job Pattern
```php
class SendTelegramMessage implements ShouldQueue
{
    public function handle()
    {
        // Make Telegram API call
        // Handle errors
        // Log if needed
    }
}
```

## Security Considerations

- **Validate all inputs**: Use Laravel's validation for all user data
- **Sanitize data**: Before storing in database
- **Parameterized queries**: Eloquent handles this automatically
- **Webhook verification**: Verify requests are from Telegram
- **Never trust user input**: Always validate and sanitize
- **Error messages**: Don't expose sensitive information
- **Rate limiting**: Use queues to prevent API abuse

## Testing Guidelines

- **TDD Approach**: Write tests before implementation
- **Test Coverage**: Aim for high coverage, especially business logic
- **Test Types**: Unit tests for services, feature tests for controllers
- **Mocking**: Mock external API calls (Telegram API)
- **Factories**: Use Laravel factories for test data
- **Run Tests**: `php artisan test` or `composer run test`

## Key Reminders

1. **TDD**: Always write tests first
2. **Thin Controllers**: Business logic belongs in services
3. **Use Queues**: For all Telegram API calls
4. **Validate Everything**: Never trust user input
5. **Type Hints**: Always use type hints and return types
6. **Laravel Conventions**: Follow them strictly
7. **PSR-12**: Format code with Laravel Pint
8. **MariaDB**: Primary database
9. **Webhooks**: Use webhook-based architecture, not polling
10. **Documentation**: Keep documentation updated

## Quick Reference

### Commands
```bash
# Create migration
php artisan make:migration create_homework_table

# Create model
php artisan make:model Homework

# Create controller
php artisan make:controller TelegramWebhookController

# Create service (manual)
# Create app/Services/HomeworkService.php

# Create job
php artisan make:job SendTelegramMessage

# Run tests
php artisan test

# Format code
./vendor/bin/pint

# Run migrations
php artisan migrate
```

### Key Files
- **Routes**: `routes/web.php` (webhook routes)
- **Models**: `app/Models/`
- **Services**: `app/Services/`
- **Controllers**: `app/Http/Controllers/`
- **Jobs**: `app/Jobs/`
- **Tests**: `tests/`
- **Migrations**: `database/migrations/`

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Telegram Bot API**: https://core.telegram.org/bots/api
- **PSR-12**: https://www.php-fig.org/psr/psr-12/
- **Project Architecture**: See `docs/ARCHITECTURE.md`
- **Project README**: See `README.md`
