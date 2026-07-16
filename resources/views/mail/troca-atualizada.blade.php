<x-mail::message>
# Olá, {{ $recipientName }}!

@foreach ($lines as $line)
{{ $line }}

@endforeach

<x-mail::button :url="$actionUrl">
Abrir no MedTurno
</x-mail::button>

Abraços,<br>
{{ config('app.name') }}
</x-mail::message>
