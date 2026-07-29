<x-mail::message>
<div style="text-align: center; margin: 0 0 18px;">
	<img src="{{ $message->embed(public_path('images/doctor-turn-email-icon.jpeg')) }}" alt="DoctorTurn" width="84" height="84" style="display: inline-block; width: 84px; height: 84px; border: 0; border-radius: 12px; object-fit: cover;">
</div>

<div style="text-align: center; margin: 0 0 28px;">
	<img src="{{ $message->embed(public_path('images/doctor-turn-email-cover.jpeg')) }}" alt="DoctorTurn" width="520" style="display: block; width: 100%; max-width: 520px; height: auto; margin: 0 auto; border: 0;">
</div>

# Olá, {{ $doctorName }}!

A escala **{{ $boardName ? "$boardName — " : '' }}{{ $monthLabel }}** do hospital **{{ $hospitalName }}** foi publicada{{ $version > 1 ? " (versão {$version})" : '' }}.

@if ($administrativeCopy)
Esta é a sua cópia administrativa da distribuição. Acesse o DoctorTurn para consultar os plantões publicados.
@else
Acesse o DoctorTurn pra ver seus plantões e confirmá-los.
@endif

<x-mail::button :url="$url">
{{ $administrativeCopy ? 'Ver escala publicada' : 'Ver minha escala' }}
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
