<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'name',
    ];

    /**
     * Get the class this subject belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Classroom, \App\Models\Subject>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the weekly schedule entries for this subject.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\WeeklyScheduleEntry>
     */
    public function weeklyScheduleEntries(): HasMany
    {
        return $this->hasMany(WeeklyScheduleEntry::class, 'subject_id');
    }

    /**
     * Get the homework records for this subject.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Homework>
     */
    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'subject_id');
    }
}
