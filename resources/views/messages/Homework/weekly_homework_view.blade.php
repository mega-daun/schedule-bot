❗️ДЗ на {{ $start_date }}-{{ $end_date }}
@foreach ($days as $day)
    {{ $day['name'] }}
    @foreach ($day['lessons'] as $hw)
        {{ $hw['subject'] }}: {{ $hw['homework'] }}
    @endforeach
@endforeach
