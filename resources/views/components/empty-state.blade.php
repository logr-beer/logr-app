@props([
    'title' => 'Nothing here yet',
    'message' => null,
    'actionLabel' => null,
    'actionHref' => null,
    'card' => false,
])

<div class="text-center py-16 {{ $card ? 'bg-white dark:bg-gray-800 rounded-xl shadow-sm' : '' }}">
    <div class="group inline-block mb-4">
        <x-application-logo-filled class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto stroke-current" />
    </div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
    @if($message)
        <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $message }}</p>
    @endif
    @if($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
            {{ $actionLabel }}
        </a>
    @endif
    {{ $slot }}
</div>
