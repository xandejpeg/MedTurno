<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return ['releases' => config('patch-notes')];
    }
}; ?>

<x-patch-notes-feed :releases="$releases" />