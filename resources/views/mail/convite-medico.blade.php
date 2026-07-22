<x-mail::message>
# Olá, {{ $invitation->name }}!

Você foi convidado(a) por **{{ $invitation->creator->name }}** para fazer parte da equipe de plantonistas do **{{ $invitation->hospital->name }}** no DoctorTurn.

<x-mail::button :url="$acceptUrl">
Aceitar convite
</x-mail::button>

Este convite expira em **{{ $invitation->expires_at->format('d/m/Y') }}**.

Se você não esperava este convite, pode ignorar este e-mail.

Abraços,<br>
{{ config('app.name') }}
</x-mail::message>
