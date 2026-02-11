# Schedule Bot Architecture

## System Overview

The Schedule Bot is a Laravel-based Telegram bot application designed to help students manage their school activities. The system receives updates from Telegram via webhooks, processes commands and messages, and stores data in a database for persistence.

## Architecture Diagram

```
┌─────────────┐
│   Telegram  │
│     API     │
└──────┬──────┘
       │ Updates(Long polling for dev period)
       ▼
┌─────────────────────────────────┐
│   Laravel Application           │
│                                 │
│  ┌──────────────────────────┐   │
│  │  Webhook Controller      |   |
|  |  or long-polling command |   |
│  │  (receives updates)      |   │
│  └───────────┬──────────────┘   │
│              │                  │
│  ┌───────────▼──────────────┐   │
│  │  TelegramUpdateHandler   |   |
│  └───────────┬──────────────┘   |
|              |                  |
│  ┌───────────▼──────────────┐   │
│  │  Bot Command Handlers    │   │
│  │  - /start                │   │
│  │  - /homework             │   │
│  │  - etc.                  │   │
│  └───────────┬──────────────┘   │
│              │                  │
│  ┌───────────▼──────────────┐   │
│  │  Service Layer           │   │
│  │  - HomeworkService       │   │
|  |  - ClassService          |   |
|  |  - etc.                  |   |
│  └───────────┬──────────────┘   │
│              │                  │
│  ┌───────────▼──────────────┐   │
│  │  Models (Eloquent)       │   │
│  │  - User                  │   │
│  │  - Homework              │   │
│  │  - Event                 │   │
│  └───────────┬──────────────┘   │
└──────────────┼──────────────────┘
               │
               ▼
        ┌──────────────┐
        │   Database   │
        └──────────────┘
```

## Data Models

### User Model
Stores Telegram user information and preferences.

**Fields:**
- `id` (unique, Telegram user ID)
- `username` (Telegram username, nullable)
- `first_name` (user's first name)
- `created_at`, `updated_at`

- **Relationships:**
  - `belongsTo` Class

### Class-based Schedule Models

#### Class Model
- Represents a school class (e.g., `10Б`).
- **Fields:**
  - `id` (primary key)
  - `code` (unique short identifier, e.g., `10Б`)
- **Relationships:**
  - `hasMany` Subject
  - `hasMany` WeeklyScheduleEntry
  - `hasMany` Homework
  - `hasMany` User

#### Subject Model
- Represents a subject taught within a specific class.
- **Fields:**
  - `id` (primary key)
  - `class_id` (foreign key to classrooms)
  - `name` (subject name, unique per class)
  - `created_at`, `updated_at`
- **Relationships:**
  - `belongsTo` Classroom
  - `hasMany` WeeklyScheduleEntry
  - `hasMany` Homework

#### WeeklyScheduleEntry Model
- Represents a single weekly timetable slot for a class.
- **Fields:**
  - `id` (primary key)
  - `class_id` (foreign key to classrooms)
  - `weekday` (1–7, Monday–Sunday)
  - `lesson_number` (1, 2, 3, ...)
  - `subject_id` (foreign key to subjects)
  - `created_at`, `updated_at`
- **Relationships:**
  - `belongsTo` Classroom
  - `belongsTo` Subject

#### Homework Model (class-based)
- Represents homework for a particular class, subject, and calendar date.
- **Fields:**
  - `id` (primary key)
  - `class_id` (foreign key to classrooms)
  - `subject_id` (foreign key to subjects)
  - `date` (date of the lesson when homework is checked)
  - `description` (homework text)
  - `created_at`, `updated_at`
- **Constraints:**
  - Unique `(class_id, subject_id, date)` to ensure at most one homework per class/subject/date.
- **Relationships:**
  - `belongsTo` Classroom
  - `belongsTo` Subject

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
- `/homework add` - Add new homework
- `/homework current <subject>` - Shows last pending(due date is after/on today) homework for specified subject
- `/homework all <subject>` - Shows all pending homeworks for specified subject

#### `/schedule`
Schedule managment commands:
- `/schedule new` - begins process of creating new schedule
- `/schedule edit` - begins process of editing current schedule
- `/schedule permit_edit <user>` - permits specified user to edit schedule
- `/schedule revoke_edit <user>` - revokes edit rights to edit schedule from specified user
- `/schedule print` - prints schedule for this week(with dates, subjects, relevant homeworks)
- `schedule print next` - prints schedule for the next week (with dates, subjects, relevant homeworks)

#### `/settings`
User settings management:
- `/settings timezone <timezone>` - Set user timezone
- `/settings language <code>` - Set preferred language

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

- **Events support**
- **Notifications**
- **File attachments**: Allow users to attach files to homework
- **Categories/Tags**: Organize homework and events by categories
- **Recurring events**: Support for repeating events (weekly classes, etc.)
- **Collaboration**: Share homework/events with classmates
- **Calendar integration**: Export to Google Calendar, iCal, etc.
