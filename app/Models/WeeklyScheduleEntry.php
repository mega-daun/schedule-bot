<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'weekday',
        'lesson_number',
        'subject_id',
    ];

    /**
     * Get the classroom that owns this weekly schedule entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Classroom, \App\Models\WeeklyScheduleEntry>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the subject for this schedule entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Subject, \App\Models\WeeklyScheduleEntry>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}

