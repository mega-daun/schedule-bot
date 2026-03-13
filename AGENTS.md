# AGENTS.md - Schedule Bot Development Guide
## Project Overview
**Schedule Bot** is a Laravel 12.x Telegram bot for student homework/schedule management.
- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+
- **Database**: MariaDB (SQLite for testing)
- **Telegram**: Webhook-based architecture
- **Code Style**: PSR-12 (via Laravel Pint)
---
## Build/Lint/Test Commands
### Running Tests
```bash
# Run all tests
composer run test
php artisan test
# Run single test class
php artisan test --filter=HomeworkTest
# Run single test method
php artisan test --filter=test_can_create_homework
# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
Code Formatting (Laravel Pint)
# Format code (PSR-12)
./vendor/bin/pint
# Check formatting without modifying
./vendor/bin/pint --test
Development Commands
# Full dev environment (server + queue + logs)
composer run dev
# Individual components
php artisan serve              # HTTP server
php artisan queue:work         # Queue worker
php artisan pail               # Log watcher
# Database
php artisan migrate
php artisan migrate:fresh
php artisan db:seed
---
Code Style Guidelines
PHP File Structure
<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class User extends Model
{
    // code here
}
- Always use declare(strict_types=1); at the top
- Use strict types mode
- One blank line after namespace declaration
Imports
- Use fully qualified class names or explicit use statements
- Group imports: built-in first, then third-party, then local
- Sort alphabetically within groups
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
Naming Conventions
Element	Convention	Example
Classes	PascalCase	HomeworkService, TelegramController
Methods	camelCase	createHomework(), sendNotification()
Variables	camelCase	$homeworkList, $chatId
Constants	UPPER_SNAKE_CASE	MAX_RETRY_COUNT
Database Tables	snake_case (plural)	homework_assignments
Database Columns	snake_case	due_date, user_id
Type Hints
- Always use type hints for method parameters
- Always specify return types
- Use union types where appropriate (PHP 8+)
public function create(array $data): Homework
public function getById(int $id): ?Homework
public function process(int|string $input): void
Formatting Rules
- Follow PSR-12 (enforced by Pint)
- Indent with 4 spaces
- Maximum line length: 120 characters
- Use braces consistently (Laravel style)
---
Architecture Patterns
MVC + Service Layer
Webhook → Controller → Service → Model → Database
                              ↓
                        Queue Job → Telegram API
- Controllers: Thin, handle request/response only
- Services: Business logic (create in app/Services/)
- Models: Eloquent relationships only
- Jobs: All Telegram API calls via queues
Bot Command Pattern
// Controller (thin)
public function handle(Request $request): Response
{
    $command = $this->parseCommand($request);
    $this->validate($command);
    
    $result = $this->homeworkService->create($command);
    
    SendTelegramMessage::dispatch($chatId, $this->format($result));
    
    return response()->json(['ok' => true]);
}
---
Error Handling
- Use Laravel's exception handling
- Log errors with context using Log::error()
- Return user-friendly messages
- Never expose stack traces to users
- Wrap external API calls in try-catch
try {
    $this->telegram->sendMessage($chatId, $text);
} catch (TelegramException $e) {
    Log::error('Telegram send failed', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
}
---
## Security Guidelines
- Validate ALL user input with Laravel validation
- Sanitize data before database storage
- Verify Telegram webhooks originate from Telegram
- Use parameterized queries (Eloquent handles this)
- Never log sensitive information (tokens, passwords)
---
Testing Guidelines
- Write tests for all new functionality
- Use TDD approach (tests first)
- Mock external APIs (Telegram)
- Test error cases, not just happy paths
- Use factories for test data
# Create test
php artisan make:test HomeworkTest
# Run tests
php artisan test
---
## Database Conventions
- Use migrations for ALL schema changes
- Follow naming: `YYYY_MM_DD_HHMMSS_description.php`
- Use Eloquent, never raw SQL
- Always include timestamps (`created_at`, `updated_at`)
- Define relationships in models
---
Key Files
Path	Purpose
app/BotCommands/	Telegram command handlers
app/Models/	Eloquent models
app/Jobs/	Queue jobs
routes/web.php	Webhook routes
database/migrations/	Schema changes
tests/	Test files
---
Quick Reference
# Format code
./vendor/bin/pint
# Run tests
php artisan test --filter=TestName
# Clear caches
php artisan config:clear
php artisan cache:clear
# Create Laravel artifacts
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:job JobName
php artisan make:migration create_table_name
php artisan make:test TestName
---
Additional Resources
- Laravel Docs: https://laravel.com/docs
- Telegram Bot API: https://core.telegram.org/bots/api
- PSR-12: https://www.php-fig.org/psr/psr-12/
- Full Rules: See .cursorrules for complete project rules
