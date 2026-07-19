<?php

declare(strict_types=1);

namespace App\DataObjects\Schedule;

use App\Models\Subject;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Represents a single day of the week within a schedule.
 *
 * Manages an ordered, sequential collection of {@see Lesson} instances.
 * Lesson numbers are always contiguous (1, 2, 3, …) — after any removal,
 * remaining lessons are automatically re-indexed.
 *
 * This class is typically accessed through {@see Schedule} rather than directly.
 */
class Weekday
{
    /**
     * @var Collection<int, Lesson> Lessons keyed by their 1-based position.
     */
    private Collection $lessons;

    /**
     * @param  int  $dayNumber  Day of week (1 = Monday, 7 = Sunday).
     */
    public function __construct(
        private readonly int $dayNumber,
    ) {
        $this->lessons = collect();
    }

    /**
     * @param  int  $dayNumber  Day of week (1 = Monday, 7 = Sunday).
     * @param  Collection<int, Lesson>  $lessons  Lessons to populate this weekday with.
     */
    public static function fromLessons(int $dayNumber, Collection $lessons): static
    {
        $weekday = new static($dayNumber);
        foreach ($lessons as $lesson) {
            $weekday->assertValidLesson($lesson);
        }
        $weekday->lessons = $lessons->map(fn (Lesson $lesson, int $index) => new Lesson($dayNumber, $index + 1, $lesson->getSubjectName(), $lesson->getSubjectId()));

        return $weekday;
    }

    /**
     * @param  mixed  $lesson  The value to validate.
     *
     * @throws InvalidArgumentException If $lesson is not a Lesson instance.
     */
    private function assertValidLesson($lesson): void
    {
        if (! $lesson instanceof Lesson) {
            throw new InvalidArgumentException;
        }
    }

    /**
     * The day of the week number (1 = Monday, 7 = Sunday).
     */
    public function getDayNumber(): int
    {
        return $this->dayNumber;
    }

    /**
     * The number that will be assigned to the next added lesson.
     */
    public function getUpstreamLessonNumber(): int
    {
        return $this->lessons->count() + 1;
    }

    /**
     * All lessons in this weekday, keyed by their position.
     *
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    /**
     * Append a new lesson at the end of this weekday.
     *
     * @param  int  $subjectId  The subject identifier.
     * @param  string  $subjectName  The subject display name.
     */
    public function addLesson(int $subjectId, string $subjectName): void
    {
        $lessonNumber = $this->getUpstreamLessonNumber();

        $this->lessons->put(
            $lessonNumber,
            new Lesson($this->dayNumber, $lessonNumber, $subjectName, $subjectId)
        );
    }

    /**
     * Remove all lessons that matches the given subject.
     *
     * After removal, remaining lessons are re-indexed to fill gaps.
     *
     * @param  int|string|Subject  $subject  Subject ID, name, or Eloquent model.
     */
    public function removeLessonsBySubject(int|string|Subject $subject): void
    {
        match (true) {
            is_int($subject) => $this->removeLessonById($subject),
            $subject instanceof Subject => $this->removeLessonById($subject->id),
            default => $this->removeLessonByName($subject),
        };
    }

    /**
     * Remove the lesson at the given position and re-index remaining lessons.
     *
     * @param  int  $lessonNumber  The 1-based position of the lesson to remove.
     */
    public function removeLessonByNumber(int $lessonNumber): void
    {
        $this->lessons->forget($lessonNumber);
        $this->reindexLessons();
    }

    /**
     * Check whether a lesson for the given subject exists in this weekday.
     *
     * @param  int|string|Subject  $subject  Subject ID, name, or Eloquent model.
     */
    public function hasLesson(int|string|Subject $subject): bool
    {
        return $this->findLessonsBySubject($subject)->count() !== 0;
    }

    /**
     * Find the first lesson matching the given subject.
     *
     * @param  int|string|Subject  $subject  Subject ID, name, or Eloquent model.
     * @return Collection<int, Lesson> The matching lesson, or null if not found.
     */
    public function findLessonsBySubject(int|string|Subject $subject): Collection
    {
        return match (true) {
            is_int($subject) => $this->lessons->filter(
                fn (Lesson $l) => $l->getSubjectId() === $subject
            ),
            $subject instanceof Subject => $this->lessons->filter(
                fn (Lesson $l) => $l->getSubjectId() === $subject->id
            ),
            default => $this->lessons->filter(
                fn (Lesson $l) => $l->getSubjectName() === $subject
            ),
        };
    }

    /**
     * Remove all lessons with the given subject ID and re-index.
     */
    private function removeLessonById(int $id): void
    {
        $this->lessons = $this->lessons->filter(
            fn (Lesson $lesson) => $lesson->getSubjectId() !== $id
        );
        $this->reindexLessons();
    }

    /**
     * Remove all lessons with the given subject name and re-index.
     */
    private function removeLessonByName(string $name): void
    {
        $this->lessons = $this->lessons->filter(
            fn (Lesson $lesson) => $lesson->getSubjectName() !== $name
        );
        $this->reindexLessons();
    }

    /**
     * Rebuild lesson numbering so that positions are always sequential (1, 2, 3, …).
     */
    private function reindexLessons(): void
    {
        $this->lessons = $this->lessons
            ->values()
            ->map(
                fn (Lesson $lesson, int $index) => new Lesson(
                    $this->dayNumber,
                    $index + 1,
                    $lesson->getSubjectName(),
                    $lesson->getSubjectId(),
                )
            );
    }
}
