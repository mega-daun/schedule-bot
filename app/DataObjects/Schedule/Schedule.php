<?php

declare(strict_types=1);

namespace App\DataObjects\Schedule;

use App\Exceptions\InvalidWeekdayException;
use App\Models\Subject;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonException;

/**
 * Representation of a full weekly schedule.
 *
 * Contains exactly 7 {@see Weekday} instances (Monday–Sunday), each holding
 * an ordered list of {@see Lesson} objects. Provides proxy methods that
 * delegate to the appropriate weekday, so callers don't need to resolve
 * the day themselves.
 *
 * Usage:
 * ```php
 * $schedule = new Schedule();
 * $schedule->addLesson(1, $subjectId, $subjectName); // add to Monday
 *
 * $schedule = Schedule::fromWeekdays($collection);
 * ```
 */
class Schedule implements Jsonable
{
    /**
     * @var Collection<int, Weekday> Weekdays keyed by day number (1–7).
     */
    private Collection $weekdays;

    /**
     * Create an empty schedule with blank weekdays.
     */
    public function __construct(private array $workDays = [1, 2, 3, 4, 5, 6, 7])
    {
        $this->weekdays = collect();
        foreach ($workDays as $day) {
            if (! is_int($day) || $day > 7 || $day < 1) {
                throw new InvalidArgumentException;
            }
            $this->weekdays->put($day, new Weekday($day));
        }
    }

    /**
     * Build a Schedule from a collection of pre-populated Weekday instances.
     *
     * @param  Collection<int, Weekday>  $weekdays
     *
     * @throws InvalidArgumentException If the collection contains non-Weekday values.
     */
    public static function fromWeekdays(Collection $weekdays): static
    {
        $schedule = new static([]);

        foreach ($weekdays as $weekday) {
            if (in_array($weekday->getDayNumber(), $schedule->workDays)) {
                throw new InvalidArgumentException;
            }
            $schedule->workDays[] = $weekday->getDayNumber();
            $schedule->assertValidWeekday($weekday);
            $schedule->weekdays->put($weekday->getDayNumber(), $weekday);
        }

        return $schedule;
    }

    /**
     * Get a specific weekday by its number.
     *
     * @param  int  $dayNumber  Day of week (1 = Monday, 7 = Sunday).
     *
     * @throws InvalidWeekdayException If $dayNumber is outside 1–7.
     */
    public function getWeekday(int $dayNumber): Weekday
    {
        $this->assertValidWeekdayNumber($dayNumber);

        return $this->weekdays->get($dayNumber);
    }

    /**
     * Get all weekdays keyed by day number.
     *
     * @return Collection<int, Weekday>
     */
    public function getWeekdays(): Collection
    {
        return $this->weekdays;
    }

    /**
     * Get subjects from the schedule, optionally filtered by weekday.
     *
     * When no weekday is specified, subjects from all weekdays are flattened
     * into a single collection. When a weekday is provided, only that day's
     * subjects are returned.
     *
     * @param  int|null  $weekday  Day of week (1 = Monday, 7 = Sunday), or null for all days.
     * @return Collection<int, array{id: int, name: string}>
     *
     * @throws InvalidArgumentException If $weekday is not a valid work day.
     */
    public function getSubjects(?int $weekday = null): Collection
    {
        if ($weekday == null) {
            return $this->getWeekdays()->flatMap(fn (Weekday $weekday) => $weekday->getSubjects());
        }

        return $this->getWeekday($weekday)->getSubjects();
    }

    /**
     * Append a lesson to the end of the specified weekday.
     *
     * @param  int  $weekday  Day of week (1–7).
     * @param  int  $subjectId  The subject identifier.
     * @param  string  $subjectName  The subject display name.
     */
    public function addLesson(int $weekday, int $subjectId, string $subjectName): void
    {
        $this->getWeekday($weekday)->addLesson($subjectId, $subjectName);
    }

    /**
     * Remove all lessons matching the given subject from the specified weekday.
     *
     * @param  int  $weekday  Day of week (1–7).
     * @param  int|string|Subject  $subject  Subject ID, name, or Eloquent model.
     */
    public function removeLessonsBySubject(int $weekday, int|string|Subject $subject): void
    {
        $this->getWeekday($weekday)->removeLessonsBySubject($subject);
    }

    /**
     * Remove the lesson at a given position from the specified weekday.
     *
     * Remaining lessons are re-indexed to fill the gap.
     *
     * @param  int  $weekday  Day of week (1–7).
     * @param  int  $lessonNumber  The 1-based position to remove.
     */
    public function removeLessonByNumber(int $weekday, int $lessonNumber): void
    {
        $this->getWeekday($weekday)->removeLessonByNumber($lessonNumber);
    }

    /**
     * Check whether a lesson for the given subject exists in the specified weekday.
     *
     * @param  int  $weekday  Day of week (1–7).
     * @param  int|string|Subject  $subject  Subject ID, name, or Eloquent model.
     */
    public function hasLesson(int $weekday, int|string|Subject $subject): bool
    {
        return $this->getWeekday($weekday)->hasLesson($subject);
    }

    /**
     * Finds all lessons matching the given subject in the specified weekday.
     *
     * @param  int  $weekday  Day of week (1–7).
     * @param  int|string|Subject  $subject  Subject ID, name, or Eloquent model.
     */
    public function findLessonsBySubject(int $weekday, int|string|Subject $subject): Collection
    {
        return $this->getWeekday($weekday)->findLessonsBySubject($subject);
    }

    /**
     * Get all lessons in the specified weekday.
     *
     * @param  int  $weekday  Day of week (1–7).
     * @return Collection<int, Lesson>
     */
    public function getLessons(?int $weekday = null): Collection
    {
        if (is_null($weekday)) {
            return $this->getWeekdays()->flatMap(fn (Weekday $weekday) => $weekday->getLessons());
        }

        return $this->getWeekday($weekday)->getLessons();
    }

    public function addWorkDay(int $weekday): void
    {
        if ($weekday > 7 or $weekday < 1 or in_array($weekday, $this->workDays)) {
            throw new InvalidArgumentException;
        }
        $this->workDays[] = $weekday;
        $this->weekdays->put($weekday, new Weekday($weekday));
    }

    public function removeWorkDay(int $weekday): void
    {
        if (! in_array($weekday, $this->workDays)) {
            throw new InvalidArgumentException;
        }
        unset($this->workDays[array_search($weekday, $this->workDays)]);
        $this->weekdays->forget($weekday);
    }

    public function hasWorkday(int $weekday): bool
    {
        return in_array($weekday, $this->workDays);
    }

    public function getWorkdays(): array
    {
        return $this->workDays;
    }

    /**
     * @throws InvalidWeekdayException If $dayNumber is outside 1–7.
     */
    private function assertValidWeekday($weekday): void
    {
        if (! $weekday instanceof Weekday) {
            throw new InvalidWeekdayException;
        }
        $this->assertValidWeekdayNumber($weekday->getDayNumber());
    }

    /**
     * @param  int  $number  Day of week (1 = Monday, 7 = Sunday).
     *
     * @throws InvalidArgumentException If $number is outside 1–7 or not a work day.
     */
    private function assertValidWeekdayNumber(int $number): void
    {
        if (! in_array($number, $this->workDays) || $number > 7 || $number < 1) {
            throw new InvalidArgumentException;
        }
    }

    public function toJson($options = 0): string
    {
        $payload = [
            'lessons' => $this->getWeekdays()
                ->flatMap(
                    fn (Weekday $weekday) => $weekday->getLessons()->toArray()
                )->values(),
            'work_days' => $this->workDays,
        ];

        return json_encode($payload, $options);
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        if (is_null($data) || ! isset($data['lessons']) || ! isset($data['work_days']) || ! is_array($data['lessons']) || ! is_array($data['work_days'])) {
            throw new JsonException('Invalid schedule structure');
        }
        [$lessons, $workDays] = [$data['lessons'], $data['work_days']];
        try {
            $schedule = new static($workDays);
        } catch (InvalidArgumentException) {
            throw new JsonException('Invalid work days');
        }
        $lessons = collect($lessons);
        if (! $lessons->every(fn ($lesson) => is_array($lesson) && isset($lesson['weekday']) && is_int($lesson['weekday']) && in_array($lesson['weekday'], $workDays) && isset($lesson['lesson_number']) && is_int($lesson['lesson_number']) && isset($lesson['subject_id']) && is_int($lesson['subject_id']) && isset($lesson['subject_name']) && is_string($lesson['subject_name']))) {
            throw new JsonException('Invalid lesson structure');
        }
        $lessons = $lessons->sortBy('weekday')->sortBy('lesson_number');
        foreach ($lessons as $lesson) {
            $schedule->addLesson($lesson['weekday'], $lesson['subject_id'], $lesson['subject_name']);
        }

        return $schedule;
    }
}
