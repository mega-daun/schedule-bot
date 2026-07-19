<?php

declare(strict_types=1);

namespace App\DataObjects\Schedule;

use App\Exceptions\InvalidWeekdayException;
use App\Models\Subject;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
class Schedule
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
    public function getLessons(int $weekday): Collection
    {
        return $this->getWeekday($weekday)->getLessons();
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
}
