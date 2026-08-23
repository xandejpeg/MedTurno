<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $brandColor = '#0f766e';

    public string $brandLogoPath = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isGestor(), 403);

        $hospital = currentHospital();
        if ($hospital !== null) {
            $this->brandColor = $hospital->brand_color ?? '#0f766e';
            $this->brandLogoPath = $hospital->brand_logo_path ?? '';
        }
    }

    public function save(): void
    {
        $hospital = currentHospital();
        abort_unless($hospital !== null, 403);

        $data = $this->validate([
            'brandColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brandLogoPath' => ['nullable', 'string', 'max:255'],
        ], attributes: ['brandColor' => 'cor principal', 'brandLogoPath' => 'logo']);

        $hospital->update([
            'brand_color' => $data['brandColor'],
            'brand_logo_path' => $data['brandLogoPath'] !== '' ? $data['brandLogoPath'] : null,
        ]);

        session()->flash('status', 'Identidade visual salva.');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'hospital' => currentHospital(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Personalizar</h2>
        <p class="text-sm text-gray-500">{{ $hospital?->name }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-teal-50 text-teal-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Identidade visual da instituição</h3>
                <p class="text-xs text-gray-500 mb-4">Personalize a cor principal e o logo exibidos para esta instituição.</p>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label for="brandColor" value="Cor principal *" />
                        <div class="mt-1 flex items-center gap-3">
                            <input wire:model.live="brandColor" id="brandColor" type="color" class="h-10 w-16 rounded border border-gray-300">
                            <x-text-input wire:model.live="brandColor" type="text" class="block w-32" placeholder="#0f766e" />
                        </div>
                        <x-input-error :messages="$errors->get('brandColor')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="brandLogoPath" value="Caminho do logo" />
                        <x-text-input wire:model="brandLogoPath" id="brandLogoPath" type="text" class="mt-1 block w-full" placeholder="images/logo.png ou URL" />
                        <p class="mt-1 text-xs text-gray-400">Deixe em branco para usar o logo padrão do DoctorTurn.</p>
                        <x-input-error :messages="$errors->get('brandLogoPath')" class="mt-1" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button type="submit">Salvar</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Pré-visualização --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Pré-visualização</h3>
                <div class="rounded-lg border border-gray-200 p-4 flex items-center gap-3" style="background-color: {{ $brandColor }}1A; border-color: {{ $brandColor }}40;">
                    @if ($brandLogoPath)
                        <img src="{{ str_starts_with($brandLogoPath, 'http') ? $brandLogoPath : asset($brandLogoPath) }}" alt="Logo" class="h-10 w-10 rounded object-contain">
                    @else
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded text-white font-bold" style="background-color: {{ $brandColor }}">DT</span>
                    @endif
                    <div>
                        <p class="font-semibold" style="color: {{ $brandColor }}">{{ $hospital?->name ?? 'Hospital' }}</p>
                        <p class="text-xs text-gray-500">Cor principal: {{ $brandColor }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
