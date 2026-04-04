<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\UnknownRoleException;
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
 * @property-read Classroom $class
 * @property datetime $created_at
 * @property datetime $updated_at
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

    public function hasClass(): bool
    {
        return $this->class_id !== null;
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
