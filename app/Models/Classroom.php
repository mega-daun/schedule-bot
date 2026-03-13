<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'join_token',
    ];

    /**
     * Get the subjects that belong to this class.
     *
     * @return HasMany<Subject>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    /**
     * Get the weekly schedule entries for this class.
     *
     * @return HasMany<WeeklyScheduleEntry>
     */
    public function weeklyScheduleEntries(): HasMany
    {
        return $this->hasMany(WeeklyScheduleEntry::class, 'class_id');
    }

    /**
     * Get the homework records for this class.
     *
     * @return HasMany<Homework>
     */
    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'class_id');
    }

    public static function generateJoinToken(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }
}
