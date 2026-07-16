<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrocaAtualizada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $lines
     */
    public function __construct(
        public string $subjectLine,
        public string $recipientName,
        public array $lines,
        public string $actionUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->subjectLine} — MedTurno",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.troca-atualizada',
            with: [
                'recipientName' => $this->recipientName,
                'lines' => $this->lines,
                'actionUrl' => $this->actionUrl,
            ],
        );
    }
}
