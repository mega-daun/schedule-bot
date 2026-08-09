<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $class_id
 * @property int $subject_id
 * @property int $weekday
 * @property int $lesson_number
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read Classroom $classroom
 * @property-read Subject $subject
 * @method static \Database\Factories\WeeklyScheduleEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereLessonNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyScheduleEntry whereWeekday($value)
 * @mixin \Eloquent
 */
class WeeklyScheduleEntry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'subject_id',
        'weekday',
        'lesson_number',
    ];

    /**
     * Get the classroom that owns this weekly schedule entry.
     *
     * @return BelongsTo<Classroom, WeeklyScheduleEntry>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
