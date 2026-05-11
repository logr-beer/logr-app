@props([
    'title',
    'actionLabel' => null,
    'actionHref' => null,
    'actionClick' => null,
])

<div class="flex items-baseline gap-3 mb-3">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
    @if($actionLabel && ($actionHref || $actionClick))
        @if($actionHref)
            <a
                href="{{ $actionHref }}"
                wire:navigate
                class="group/add inline-flex items-center gap-0 pl-1.5 pr-1.5 py-0.5 bg-amber-600 text-white text-xs font-medium rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all"
            >
                <x-icon name="plus" size="3.5" />
                <span class="max-w-0 overflow-hidden group-hover/add:max-w-[4rem] group-focus/add:max-w-[4rem] transition-all duration-200 whitespace-nowrap">
                    &nbsp;{{ $actionLabel }}
                </span>
            </a>
        @else
            <button
                type="button"
                wire:click="{{ $actionClick }}"
                class="group/add inline-flex items-center gap-0 pl-1.5 pr-1.5 py-0.5 bg-amber-600 text-white text-xs font-medium rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all"
            >
                <x-icon name="plus" size="3.5" />
                <span class="max-w-0 overflow-hidden group-hover/add:max-w-[4rem] group-focus/add:max-w-[4rem] transition-all duration-200 whitespace-nowrap">
                    &nbsp;{{ $actionLabel }}
                </span>
            </button>
        @endif
    @endif
</div>
