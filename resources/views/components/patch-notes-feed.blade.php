@props(['releases'])

<div class="mx-auto max-w-5xl px-4 py-7 sm:px-6 lg:px-8 lg:py-10">
    <header class="border-b border-gray-200 pb-7">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-3 flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#073f3d] text-white" aria-hidden="true">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5V6.75a3.375 3.375 0 00-3.375-3.375H8.625m0 12.75h7.5m-7.5 3h4.5m-6.75 1.5h9.75a3.375 3.375 0 003.375-3.375V11.04a3.375 3.375 0 00-.988-2.386l-4.166-4.166a3.375 3.375 0 00-2.386-.988H6.375A1.875 1.875 0 004.5 5.375v13.5a1.875 1.875 0 001.875 1.875z"/></svg>
                    </span>
                    <p class="text-xs font-semibold uppercase text-teal-700">Evolução do produto</p>
                </div>
                <h1 class="text-3xl font-bold text-gray-950 sm:text-4xl">Patch Notes</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 sm:text-base">Acompanhe cada novidade, melhoria e correção que chega ao DoctorTurn.</p>
            </div>
            @if (count($releases) > 0)
                <div class="shrink-0 border-l-2 border-lime-500 pl-4">
                    <p class="text-xs font-medium text-gray-500">Versão atual</p>
                    <p class="mt-0.5 text-xl font-bold text-gray-950">v{{ $releases[0]['version'] }}</p>
                </div>
            @endif
        </div>
    </header>

    <div class="mt-8 space-y-12">
        @forelse ($releases as $release)
            <article class="relative sm:grid sm:grid-cols-[130px_minmax(0,1fr)] sm:gap-8">
                <aside class="mb-5 sm:mb-0">
                    <span class="inline-flex rounded-md bg-lime-100 px-2.5 py-1 text-sm font-bold text-lime-800">v{{ $release['version'] }}</span>
                    <time class="mt-2 block text-xs text-gray-500">{{ $release['released_at'] }}</time>
                    @if ($loop->first)
                        <span class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                            Versão atual
                        </span>
                    @endif
                </aside>

                <div class="min-w-0 border-l border-gray-200 pl-5 sm:pl-8">
                    <h2 class="text-2xl font-bold text-gray-950">{{ $release['title'] }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 sm:text-base">{{ $release['summary'] }}</p>

                    <section aria-label="Destaques da versão {{ $release['version'] }}" class="mt-7 grid gap-3 md:grid-cols-3">
                        @foreach ($release['highlights'] as $highlight)
                            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm shadow-gray-900/5">
                                <span class="mb-3 block h-1 w-9 rounded-full bg-teal-500"></span>
                                <h3 class="font-semibold text-gray-900">{{ $highlight['title'] }}</h3>
                                <p class="mt-1.5 text-sm leading-5 text-gray-600">{{ $highlight['description'] }}</p>
                            </div>
                        @endforeach
                    </section>

                    <div class="mt-8 grid gap-x-8 gap-y-7 md:grid-cols-2">
                        @foreach ($release['sections'] as $section)
                            <section>
                                <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900">
                                    <span class="h-2 w-2 rounded-sm bg-lime-500" aria-hidden="true"></span>
                                    {{ $section['title'] }}
                                </h3>
                                <ul class="mt-3 space-y-2.5">
                                    @foreach ($section['items'] as $item)
                                        <li class="flex gap-2.5 text-sm leading-5 text-gray-600">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <div class="border-l-2 border-gray-200 py-4 pl-5">
                <h2 class="font-semibold text-gray-900">Nenhuma versão publicada</h2>
                <p class="mt-1 text-sm text-gray-500">As próximas novidades do DoctorTurn aparecerão aqui.</p>
            </div>
        @endforelse
    </div>
</div>