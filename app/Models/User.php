<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property UserRole $role
 * @property-read Classroom $class
 */
class User extends Model
{
    use HasFactory;

    protected $table = 'users';

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
