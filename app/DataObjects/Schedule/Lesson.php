<?php

declare(strict_types=1);

namespace App\DataObjects\Schedule;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Immutable value object representing a single lesson within a weekday schedule.
 *
 * A Lesson ties a subject to a specific position (number) within a weekday.
 * It is always created and managed by a {@see Weekday} instance.
 */
class Lesson implements Arrayable
{
    /**
     * @param  int  $weekday  Day of week (1 = Monday, 7 = Sunday).
     * @param  int  $number  The 1-based position within the weekday.
     * @param  string  $subjectName  The subject display name.
     * @param  int  $subjectId  The subject identifier.
     */
    public function __construct(
        private readonly int $weekday,
        private readonly int $number,
        private readonly string $subjectName,
        private readonly int $subjectId
    ) {}

    /**
     * The position of this lesson within the weekday (1-based).
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * The display name of the subject taught in this lesson.
     */
    public function getSubjectName(): string
    {
        return $this->subjectName;
    }

    /**
     * The identifier of the subject taught in this lesson.
     */
    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    /**
     * The day of the week this lesson belongs to (1 = Monday, 7 = Sunday).
     */
    public function getWeekday(): int
    {
        return $this->weekday;
    }

    public function toArray(): array
    {
        return [
            'subject_id' => $this->subjectId,
            'subject_name' => $this->subjectName,
            'weekday' => $this->weekday,
            'lesson_number' => $this->number,
        ];
    }
}
