❗️ДЗ на: {{ $date }}
@foreach ($lessons as $hw)
    {{ $hw['subject'] }}: {{ $hw['homework'] }}
@endforeach
