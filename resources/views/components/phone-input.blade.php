@props([
    'countryModel',
    'numberModel',
    'id' => 'phone',
    'label' => 'Celular com WhatsApp',
    'required' => false,
])

@php($countries = \App\Support\PhoneNumber::countries())

<div
    x-data="{
        countries: @js($countries),
        country: $wire.entangle(@js($countryModel)).live,
        open: false,
        search: '',
        normalize(value) {
            return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        },
        filteredCountries() {
            const query = this.normalize(this.search.replace('+', '').trim());

            if (! query) return this.countries;

            return this.countries.filter((item) =>
                this.normalize(item.name).includes(query)
                || item.calling_code.includes(query)
                || item.iso.toLowerCase().includes(query)
            );
        },
        selectedCountry() {
            return this.countries.find((item) => item.iso === this.country) ?? this.countries.find((item) => item.iso === 'BR');
        },
        selectCountry(iso) {
            this.country = iso;
            this.open = false;
            this.search = '';
        },
    }"
    x-on:keydown.escape.window="open = false"
    class="space-y-1"
>
    <x-input-label :for="$id.'-number'" :value="$label.($required ? ' *' : '')" />

    <div class="grid grid-cols-[minmax(9.5rem,0.8fr)_minmax(0,1.5fr)] gap-2">
        <div class="relative" x-on:click.outside="open = false">
            <button
                type="button"
                x-on:click="open = ! open; if (open) $nextTick(() => $refs.countrySearch.focus())"
                x-bind:aria-expanded="open"
                aria-haspopup="listbox"
                class="phone-country-trigger flex h-[42px] w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 text-left text-sm text-gray-800 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <span class="fi shrink-0 rounded-[2px]" x-bind:class="`fi-${selectedCountry().iso.toLowerCase()}`"></span>
                    <span class="truncate" x-text="`${selectedCountry().iso} +${selectedCountry().calling_code}`"></span>
                </span>
                <span aria-hidden="true" class="phone-country-arrow ml-2 text-gray-400">&#9662;</span>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.origin.top.left
                class="phone-country-menu absolute z-50 mt-1 w-80 max-w-[calc(100vw-3rem)] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
            >
                <div class="phone-country-search-wrap border-b border-gray-100 p-2">
                    <input
                        x-ref="countrySearch"
                        x-model="search"
                        type="search"
                        placeholder="Buscar país ou código"
                        autocomplete="off"
                        class="block h-10 w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                </div>

                <div role="listbox" class="phone-country-list max-h-64 overflow-y-auto py-1">
                    <template x-for="item in filteredCountries()" :key="item.iso">
                        <button
                            type="button"
                            role="option"
                            x-on:click="selectCountry(item.iso)"
                            x-bind:aria-selected="country === item.iso"
                            class="phone-country-option flex w-full items-center justify-between gap-4 px-3 py-2 text-left text-sm hover:bg-teal-50 focus:bg-teal-50 focus:outline-none"
                            x-bind:class="country === item.iso ? 'bg-teal-50 text-teal-800' : 'text-gray-700'"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="fi shrink-0 rounded-[2px]" x-bind:class="`fi-${item.iso.toLowerCase()}`"></span>
                                <span class="truncate" x-text="item.name"></span>
                            </span>
                            <span class="phone-country-code shrink-0 font-medium text-gray-500" x-text="`+${item.calling_code}`"></span>
                        </button>
                    </template>

                    <p x-show="filteredCountries().length === 0" class="px-3 py-4 text-center text-sm text-gray-500">
                        Nenhum país encontrado.
                    </p>
                </div>
            </div>
        </div>

        <input
            wire:model="{{ $numberModel }}"
            id="{{ $id }}-number"
            type="tel"
            inputmode="tel"
            autocomplete="tel-national"
            @required($required)
            placeholder="Número com DDD"
            class="block h-[42px] w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
        />
    </div>

    <x-input-error :messages="$errors->get($countryModel)" />
    <x-input-error :messages="$errors->get($numberModel)" />
</div>