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
    ];

    /**
     * Get the subjects that belong to this class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Subject>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    /**
     * Get the weekly schedule entries for this class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\WeeklyScheduleEntry>
     */
    public function weeklyScheduleEntries(): HasMany
    {
        return $this->hasMany(WeeklyScheduleEntry::class, 'class_id');
    }

    /**
     * Get the homework records for this class.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Homework>
     */
    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'class_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }
}

