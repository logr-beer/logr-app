@props(['options', 'wireModel', 'placeholder' => 'Select...', 'id' => null])

@php $selectId = $id ?? 'select-' . uniqid(); @endphp

<div
    x-data="{
        open: false,
        value: @entangle($wireModel),
        get label() {
            const opts = {{ Js::from($options) }};
            return opts[this.value] ?? '{{ $placeholder }}';
        }
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        x-on:click="open = !open"
        id="{{ $selectId }}"
        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 focus:ring-1 focus:outline-none transition-colors"
    >
        <span x-text="label" class="truncate"></span>
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
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
        @foreach($options as $value => $label)
            <button
                type="button"
                x-on:click="value = '{{ $value }}'; open = false"
                class="w-full text-left px-3 py-2 text-sm transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/20"
                :class="value === '{{ $value }}' && 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 font-medium'"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>
