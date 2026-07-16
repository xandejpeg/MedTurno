<?php

namespace App\Mail;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EscalaPublicada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Schedule $schedule,
        public string $doctorName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sua escala de {$this->schedule->monthLabel()} está publicada — MedTurno",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.escala-publicada',
            with: [
                'doctorName' => $this->doctorName,
                'hospitalName' => $this->schedule->hospital->name,
                'boardName' => $this->schedule->board->name,
                'monthLabel' => $this->schedule->monthLabel(),
                'version' => $this->schedule->version,
                'url' => route('dashboard'),
            ],
        );
    }
}
