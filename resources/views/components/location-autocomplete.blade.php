@props([
    'label',
    'prefix',
    'model',
    'selectedId' => null,
    'selectedName' => '',
    'suggestions' => [],
    'apiResults' => [],
    'icon' => 'map-pin',
    'placeholder' => 'Search for a location...',
])

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>

    @if($selectedId)
        <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
            <x-icon :name="$icon" size="4" class="text-amber-500 flex-shrink-0" />
            <span class="text-sm text-gray-900 dark:text-white flex-1">{{ $selectedName }}</span>
            <button type="button" wire:click="clearLocation('{{ $prefix }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-icon name="x-mark" size="4" />
            </button>
        </div>
    @else
        <div class="relative">
            <x-icon name="search" size="4" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
                wire:model.live.debounce.300ms="{{ $prefix }}Query"
                @focus="open = true"
                @input="open = true"
                type="text"
                autocomplete="off"
                placeholder="{{ $placeholder }}"
                class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
            />
        </div>

        @if(count($suggestions) > 0 || count($apiResults) > 0)
            <div x-show="open" x-cloak x-transition class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                @foreach($suggestions as $item)
                    @php $itemId = is_array($item) ? $item['id'] : $item->id; @endphp
                    @php $itemName = is_array($item) ? $item['name'] : $item->name; @endphp
                    @php $itemLocation = is_array($item) ? implode(', ', array_filter([$item['city'] ?? null, $item['state'] ?? null, $item['country'] ?? null])) : $item->displayLocation(); @endphp
                    <button
                        type="button"
                        wire:click="selectLocation('{{ $prefix }}', {{ $itemId }}, '{{ addslashes($model) }}')"
                        @click="open = false"
                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2"
                    >
                        <x-icon :name="$icon" size="4" class="text-gray-400 flex-shrink-0" />
                        <div class="min-w-0">
                            <span class="text-gray-900 dark:text-white">{{ $itemName }}</span>
                            @if($itemLocation)
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ $itemLocation }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach

                @if(count($apiResults) > 0)
                    @if(count($suggestions) > 0)
                        <div class="border-t border-gray-200 dark:border-gray-600 px-4 py-1.5">
                            <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Search Results</span>
                        </div>
                    @endif
                    @foreach($apiResults as $result)
                        <button
                            type="button"
                            wire:click="importAndSelectLocation('{{ $prefix }}', '{{ $result['_key'] }}', '{{ addslashes($model) }}')"
                            @click="open = false"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors flex items-center gap-2"
                        >
                            <x-icon name="map-pin" size="4" class="text-amber-500 flex-shrink-0" />
                            <div class="min-w-0">
                                <span class="text-gray-900 dark:text-white">{{ $result['name'] }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ collect([$result['address'], $result['city'], $result['state']])->filter()->implode(', ') }}</p>
                            </div>
                        </button>
                    @endforeach
                @endif
            </div>
        @endif
    @endif
</div>
