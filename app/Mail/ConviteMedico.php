<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConviteMedico extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convite para o '.$this->invitation->hospital->name.' — MedTurno',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.convite-medico',
            with: [
                'acceptUrl' => route('convite.aceitar', ['token' => $this->token]),
            ],
        );
    }
}
