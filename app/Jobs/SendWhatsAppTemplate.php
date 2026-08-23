<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppTemplate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    /**
     * @param  list<string>  $parameters
     */
    public function __construct(
        public string $phone,
        public string $template,
        public array $parameters,
        public ?int $logUserId = null,
        public ?string $logBody = null,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $whatsApp->sendTemplate($this->phone, $this->template, $this->parameters);
    }
}
