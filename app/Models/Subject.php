<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $class_id
 * @property string $name
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read Classroom $classroom
 * @property-read \Illuminate\Database\Eloquent\Collection<WeeklyScheduleEntry> $weeklyScheduleEntries
 * @property-read \Illuminate\Database\Eloquent\Collection<Homework> $homeworks
 */
class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'class_id',
        'name',
    ];

    /**
     * Get the class this subject belongs to.
     *
     * @return BelongsTo<Classroom, Subject>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the weekly schedule entries for this subject.
     *
     * @return HasMany<WeeklyScheduleEntry>
     */
    public function weeklyScheduleEntries(): HasMany
    {
        return $this->hasMany(WeeklyScheduleEntry::class, 'subject_id');
    }

    /**
     * Get the homework records for this subject.
     *
     * @return HasMany<Homework>
     */
    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'subject_id');
    }
}
