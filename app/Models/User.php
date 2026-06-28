<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\UnknownRoleException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id Telegram user ID
 * @property string $first_name
 * @property string $username
 * @property string|null $timezone
 * @property string|null $language_code
 * @property int|null $class_id
 * @property bool $is_bot
 * @property UserRole $role
 * @property-read Classroom|null $class
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 */
class User extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'first_name',
        'username',
        'role',
        'is_bot',
        'language_code',
        'class_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
        ];
    }

    /**
     * @return BelongsTo<Classroom>
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function isAdmin(): bool
    {
        return $this->role == UserRole::Admin;
    }

    public function isOnDutyOrHigher(): bool
    {
        return in_array($this->role, [UserRole::OnDuty, UserRole::Teacher, UserRole::Admin], true);
    }

    public function hasClass(): bool
    {
        return $this->class_id !== null;
    }

    public function joinClass(int $classId)
    {
        $this->update([
            'class_id' => $classId,
            'role' => UserRole::Student,
        ]);
    }

    public function joinClassAsAdmin(int $classId)
    {
        $this->update([
            'class_id' => $classId,
            'role' => UserRole::Admin,
        ]);
    }

    public function leaveClass()
    {
        $this->update([
            'class_id' => null,
            'role' => UserRole::Student,
        ]);
    }

    public function changeRole(string|UserRole $newRole): void
    {
        if (is_string($newRole)) {
            $newRole = UserRole::tryFrom($newRole);
        }
        if ($newRole === null) {
            throw new UnknownRoleException;
        }
        $this->update(['role' => $newRole]);
    }
}
