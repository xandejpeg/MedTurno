<?php

use App\Models\Notification;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /** @var array<int, int> */
    public array $justRead = [];

    public function mount(): void
    {
        $this->justRead = auth()->user()->notifications()
            ->whereNull('read_at')
            ->pluck('id')
            ->all();

        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'notifications' => auth()->user()->notifications()
                ->latest()
                ->limit(50)
                ->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notificações</h2>
    </x-slot>

    <div class="py-12 pb-24 sm:pb-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @forelse ($notifications as $notification)
                    @php($isNew = $notification->read_at === null || in_array($notification->id, $justRead, true))
                    <div class="p-5 {{ ! $loop->last ? 'border-b border-gray-100' : '' }} {{ $isNew ? 'bg-teal-50/50' : '' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">
                                    @if ($isNew)
                                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500 me-1"></span>
                                    @endif
                                    {{ $notification->title }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">{{ $notification->body }}</p>
                                @if ($notification->link)
                                    <a href="{{ $notification->link }}" wire:navigate class="text-sm text-teal-600 hover:underline mt-1 inline-block">Ver →</a>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">Nenhuma notificação.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
