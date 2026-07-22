<x-mail::message>
# Olá, {{ $doctorName }}!

A escala **{{ $boardName ? "$boardName — " : '' }}{{ $monthLabel }}** do hospital **{{ $hospitalName }}** foi publicada{{ $version > 1 ? " (versão {$version})" : '' }}.

Acesse o DoctorTurn pra ver seus plantões e confirmá-los.

<x-mail::button :url="$url">
Ver minha escala
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
