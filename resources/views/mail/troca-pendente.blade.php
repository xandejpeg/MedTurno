<x-mail::message>
# Olá, {{ $recipientName }}!

Há uma **troca de plantão aguardando aprovação** no **{{ $hospitalName }}**.

<x-mail::panel>
**Plantão:** {{ $when }}
**De:** {{ $fromName }}
**Para:** {{ $toName }}
@if ($reason)
**Motivo:** {{ $reason }}
@endif
</x-mail::panel>

Acesse o DoctorTurn para aprovar ou recusar esta troca.

<x-mail::button :url="$actionUrl">
Revisar trocas
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
