<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface SubjectRepository
{
    public function getSubjects(int $class_id, array $columns = ['*']): Collection;
}
