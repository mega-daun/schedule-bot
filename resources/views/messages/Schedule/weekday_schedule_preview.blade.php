Расписание на {{$weekday}}:
@foreach ($lessons as $number => $lesson)
    {{$number}}. {{$lesson->getSubjectName()}}
@endforeach

