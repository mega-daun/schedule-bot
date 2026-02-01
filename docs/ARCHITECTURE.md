# Schedule Bot Architecture

## System Overview

The Schedule Bot is a Laravel-based Telegram bot application designed to help students manage their school activities. The system receives updates from Telegram via webhooks, processes commands and messages, and stores data in a database for persistence.

## Architecture Diagram

```
┌─────────────┐
│   Telegram  │
│     API    │
└──────┬──────┘
       │ Webhook Updates
       ▼
┌─────────────────────────────────┐
│   Laravel Application           │
│                                 │
│  ┌──────────────────────────┐  │
│  │  Webhook Controller       │  │
│  │  (receives updates)       │  │
│  └───────────┬──────────────┘  │
│              │                  │
│  ┌───────────▼──────────────┐  │
│  │  Bot Command Handlers    │  │
│  │  - /start                 │  │
│  │  - /homework              │  │
│  │  - /events                │  │
│  │  - /list                  │  │
│  └───────────┬──────────────┘  │
│              │                  │
│  ┌───────────▼──────────────┐  │
│  │  Service Layer           │  │
│  │  - HomeworkService       │  │
│  │  - EventService          │  │
│  │  - NotificationService   │  │
│  └───────────┬──────────────┘  │
│              │                  │
│  ┌───────────▼──────────────┐  │
│  │  Models (Eloquent)       │  │
│  │  - User                  │  │
│  │  - Homework              │  │
│  │  - Event                 │  │
│  └───────────┬──────────────┘  │
└──────────────┼──────────────────┘
               │
               ▼
        ┌──────────────┐
        │   Database   │
        │   (SQLite)   │
        └──────────────┘
```

## Data Models

### User Model
Stores Telegram user information and preferences.

**Fields:**
- `id` (primary key)
- `telegram_id` (unique, Telegram user ID)
- `username` (Telegram username, nullable)
- `first_name` (user's first name)
- `last_name` (user's last name, nullable)
- `language_code` (preferred language)
- `timezone` (user's timezone for reminders)
- `created_at`, `updated_at`

**Relationships:**
- `hasMany` Homework
- `hasMany` Event

### Homework Model
Represents a homework assignment.

**Fields:**
- `id` (primary key)
- `user_id` (foreign key to users)
- `title` (assignment title)
- `description` (detailed description, nullable)
- `subject` (subject/course name)
- `due_date` (deadline date and time)
- `completed` (boolean, default false)
- `priority` (enum: low, medium, high)
- `created_at`, `updated_at`

**Relationships:**
- `belongsTo` User

### Event Model
Represents a school event or important date.

**Fields:**
- `id` (primary key)
- `user_id` (foreign key to users)
- `title` (event title)
- `description` (event description, nullable)
- `event_date` (date and time of the event)
- `event_type` (enum: exam, deadline, meeting, other)
- `location` (location, nullable)
- `reminder_sent` (boolean, default false)
- `created_at`, `updated_at`

**Relationships:**
- `belongsTo` User

## Bot Commands

### Core Commands

#### `/start`
Initializes the bot for a new user.
- Creates or updates user record in database
- Sends welcome message with available commands
- Provides quick start guide

#### `/help`
Displays help message with all available commands and their usage.

#### `/homework`
Homework management commands:
- `/homework add <title> | <subject> | <due_date>` - Add new homework
- `/homework list` - List all homework assignments
- `/homework list pending` - List only pending assignments
- `/homework complete <id>` - Mark homework as completed
- `/homework delete <id>` - Delete a homework assignment
- `/homework view <id>` - View details of a specific homework

#### `/events`
Event management commands:
- `/events add <title> | <date> | <type>` - Add new event
- `/events list` - List all events
- `/events list upcoming` - List upcoming events
- `/events delete <id>` - Delete an event
- `/events view <id>` - View details of a specific event

#### `/list` or `/all`
Shows a combined view of all homework and events, sorted by date.

#### `/settings`
User settings management:
- `/settings timezone <timezone>` - Set user timezone
- `/settings language <code>` - Set preferred language

## Workflow

### User Registration Flow
1. User sends `/start` command to bot
2. Webhook receives update from Telegram
3. Webhook controller extracts user information
4. System checks if user exists in database
5. If new user, creates User record
6. Sends welcome message with instructions

### Adding Homework Flow
1. User sends `/homework add` command with details
2. Command handler parses the input
3. Validates required fields (title, subject, due_date)
4. Creates Homework record linked to user
5. Confirms creation with formatted message
6. Optionally schedules reminder notification

### Notification Flow
1. Scheduled job runs periodically (via Laravel scheduler)
2. Queries database for upcoming homework/events
3. Checks if reminder should be sent (based on user preferences)
4. Sends notification via Telegram API
5. Marks reminder as sent in database

### Data Flow Example: Adding Homework

```
User Input: /homework add Math Assignment | Mathematics | 2024-12-20 23:59

1. Telegram API → Webhook Endpoint
   POST /webhook/telegram
   {
     "message": {
       "text": "/homework add Math Assignment | Mathematics | 2024-12-20 23:59",
       "from": { "id": 123456, "username": "student" }
     }
   }

2. WebhookController → BotCommandHandler
   - Extracts command and parameters
   - Identifies user from Telegram ID
   - Routes to HomeworkCommandHandler

3. HomeworkCommandHandler → HomeworkService
   - Parses input: title, subject, due_date
   - Validates date format and future date
   - Creates Homework model instance

4. HomeworkService → Database
   - Saves homework to database
   - Links to user via user_id

5. Response → Telegram API
   - Sends confirmation message
   - "✅ Homework added: Math Assignment (Due: Dec 20, 2024)"
```

## Service Layer

### HomeworkService
Handles all homework-related business logic:
- Creating homework assignments
- Updating homework status
- Querying homework by various criteria
- Formatting homework data for display

### EventService
Handles all event-related business logic:
- Creating events
- Querying upcoming events
- Managing event reminders

### NotificationService
Manages reminder notifications:
- Scheduling reminders
- Sending notifications via Telegram
- Tracking sent reminders
- Handling notification preferences

### TelegramService
Wrapper for Telegram Bot API interactions:
- Sending messages
- Formatting messages with Markdown/HTML
- Handling inline keyboards
- Error handling for API calls

## Queue System

The application uses Laravel's queue system for:
- **Asynchronous notifications**: Sending reminders without blocking the webhook response
- **Background processing**: Heavy operations that don't need immediate response
- **Rate limiting**: Respecting Telegram API rate limits

## Security Considerations

- **Webhook verification**: Validate incoming webhook requests
- **User authentication**: Ensure commands come from valid Telegram users
- **Input validation**: Sanitize and validate all user inputs
- **SQL injection prevention**: Use Eloquent ORM (parameterized queries)
- **Rate limiting**: Prevent abuse of bot commands
- **Error handling**: Don't expose sensitive information in error messages

## Future Enhancements

- **Categories/Tags**: Organize homework and events by categories
- **Recurring events**: Support for repeating events (weekly classes, etc.)
- **File attachments**: Allow users to attach files to homework
- **Collaboration**: Share homework/events with classmates
- **Calendar integration**: Export to Google Calendar, iCal, etc.
- **Analytics**: Track completion rates and productivity metrics
- **Multi-language support**: Full internationalization
