@props(['options', 'sortField' => 'sortBy', 'directionField' => 'sortDirection'])

@php $selectId = 'sort-' . uniqid(); @endphp

<div class="inline-flex items-stretch flex-shrink-0">
    <div
        x-data="{
            open: false,
            value: @entangle($sortField),
            get label() {
                const opts = {{ Js::from($options) }};
                return opts[this.value] ?? 'Sort';
            }
        }"
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        x-on:focusout="if (!$el.contains($event.relatedTarget)) open = false"
        class="relative"
    >
        <button
            type="button"
            x-on:click="open = !open"
            class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-l-lg border-r-0 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors"
        >
            <span x-text="label" class="truncate"></span>
            <x-icon name="chevron-down" size="4" class="text-gray-400 flex-shrink-0 transition-transform" ::class="{ 'rotate-180': open }" />
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full min-w-[8rem] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-auto max-h-60"
        >
            @foreach($options as $val => $label)
                <button
                    type="button"
                    x-on:click="value = '{{ $val }}'; open = false"
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
    <button
        wire:click="$set('{{ $directionField }}', '{{ $this->{$directionField} === 'asc' ? 'desc' : 'asc' }}')"
        class="inline-flex items-center px-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500 dark:text-gray-400 hover:text-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors"
        title="{{ $this->{$directionField} === 'asc' ? 'Ascending' : 'Descending' }}"
    >
        @if($this->{$directionField} === 'asc')
            <x-icon name="arrow-up" size="4" />
        @else
            <x-icon name="arrow-down" size="4" />
        @endif
    </button>
</div>
