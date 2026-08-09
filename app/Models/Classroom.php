<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Jobs\BroadcastToUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $join_token
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<WeeklyScheduleEntry> $weeklyScheduleEntries
 * @property-read \Illuminate\Database\Eloquent\Collection<Homework> $homeworks
 * @property-read \Illuminate\Database\Eloquent\Collection<Subject> $subjects
 * @property-read \Illuminate\Database\Eloquent\Collection<User> $users
 * @property-read int|null $homeworks_count
 * @property-read int|null $subjects_count
 * @property-read int|null $users_count
 * @property-read int|null $weekly_schedule_entries_count
 * @method static \Database\Factories\ClassroomFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom whereJoinToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Classroom whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

    /**
     * Get the subjects for this class.
     *
     * @return HasMany<Subject>
     */
    public function subjects(): HasMany {
        return $this->hasMany(Subject::class, 'class_id');
    }

    public static function generateJoinToken(): string
    {
        return bin2hex(random_bytes(8));
    }

    protected static function booted(): void
    {
        static::deleting(function (Classroom $classroom) {
            $userIds = $classroom->users()->pluck('id');
            BroadcastToUsers::dispatch($userIds, __('info.class.deleted_broadcast'));

            $classroom->users()->update([
                'role' => UserRole::Student,
            ]);
        });
    }

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }
}
