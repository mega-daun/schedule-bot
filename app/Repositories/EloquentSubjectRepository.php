<?php

namespace App\Repositories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;

class EloquentSubjectRepository implements SubjectRepository
{
    public function getSubjects(int $class_id, array $columns = ['*']): Collection
    {
        return Subject::where('class_id', $class_id)->get($columns);
    }
}
