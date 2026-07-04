<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $class_id
 * @property \Carbon\Carbon $date
 * @property string $description
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
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
}
