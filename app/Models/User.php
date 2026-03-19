<?php

declare(strict_types=1);

namespace App\Models;

use App\BotCommands\Conversations\Conversation;
use App\BotCommands\Conversations\NewClassConversation;
use App\Enums\UserRole;
use App\Traits\HasConversation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id Referencing user's telegram id
 * @property string $first_name
 * @property string $username
 * @property string $language_code
 * @property int $class_id
 * @property bool is_bot
 * @property UserRole $role
 * @property array|null $conversation_state
 * @property-read Classroom $class
 * @property datetime $created_at
 * @property datetime $updated_at
 *
 * @see HasConversation For conversation management methods
 *
 * @method bool hasActiveConversation()
 * @method void startConversation(string $action, array $data = [])
 * @method void clearConversationState()
 * @method string|null getConversationAction()
 * @method array getConversationData()
 * @method void updateConversationData(array $data)
 *
 * ## Conversation State
 *
 * The `conversation_state` JSON column stores the current multi-message
 * conversation flow for the user. Structure:
 * ```php
 * [
 *     'action' => string,      // Conversation action identifier (matches registered handler)
 *     'data'   => array|null, // Additional context for the conversation
 * ]
 * ```
 *
 * ## Available Conversation Actions
 *
 * | Action | Class | Description |
 * |--------|-------|-------------|
 * | `'newclass'` | NewClassConversation | Creating a new class (awaiting class name input) |
 *
 * @see Conversation Base class for conversations
 * @see NewClassConversation Example conversation implementation
 */
class User extends Model
{
    use HasConversation, HasFactory;

    public $incrementing = false;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'first_name',
        'username',
        'role',
        'is_bot',
        'language_code',
        'class_id',
        'conversation_state',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'conversation_state' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Classroom>
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
