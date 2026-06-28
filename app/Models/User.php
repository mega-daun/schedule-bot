<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
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
}
