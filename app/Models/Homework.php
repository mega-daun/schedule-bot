<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $class_id
 * @property int $subject_id
 * @property Carbon $date
 * @property string $description
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Classroom $classroom
 * @property-read Subject $subject
 * @method static \Database\Factories\HomeworkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Homework whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Homework extends Model
{
    use HasFactory;

    protected $table = 'homeworks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'date',
        'description',
        'subject_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    /**
     * Get the classroom this homework belongs to.
     *
     * @return BelongsTo<Classroom, Homework>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
