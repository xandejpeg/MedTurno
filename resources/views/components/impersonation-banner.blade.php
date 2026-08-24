@php
    $impersonating = session()->has(\App\Services\ImpersonationService::SESSION_KEY);
    $viewed = $impersonating ? auth()->user() : null;
@endphp

@if ($impersonating && $viewed !== null)
    <div class="sticky top-0 z-50 border-b border-amber-500/40 bg-amber-400 text-amber-950 shadow-sm print:hidden">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-3 gap-y-2 px-4 py-2 sm:px-6 lg:px-8">
            <span class="flex items-center gap-1.5 rounded-full bg-amber-950/10 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide">
                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Modo visualização
            </span>

            <p class="min-w-0 flex-1 text-sm">
                Você está vendo o sistema como
                <strong class="font-semibold">{{ $viewed->name }}</strong>
                <span class="hidden text-amber-900/80 sm:inline">
                    ({{ $viewed->email }}{{ $viewed->role !== null ? ' · '.$viewed->role->label() : '' }})
                </span>
            </p>

            <form method="POST" action="{{ route('impersonate.stop') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-md bg-amber-950 px-3 py-1.5 text-xs font-semibold text-amber-50 transition hover:bg-amber-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-950 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-400">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar ao painel admin
                </button>
            </form>
        </div>
    </div>
@endif
