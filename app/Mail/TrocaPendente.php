<?php

namespace App\Mail;

use App\Models\ShiftTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrocaPendente extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShiftTransfer $transfer,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Troca aguardando aprovação — DoctorTurn',
        );
    }

    public function content(): Content
    {
        $shift = $this->transfer->shift;

        return new Content(
            markdown: 'mail.troca-pendente',
            with: [
                'recipientName' => $this->recipientName,
                'fromName' => $this->transfer->fromDoctor?->name ?? '—',
                'toName' => $this->transfer->toDoctor?->name ?? '—',
                'when' => $shift->date->format('d/m/Y').' às '.$shift->starts_at->format('H:i'),
                'hospitalName' => $shift->hospital->name,
                'reason' => $this->transfer->reason,
                'actionUrl' => route('gestor.trocas'),
            ],
        );
    }
}
