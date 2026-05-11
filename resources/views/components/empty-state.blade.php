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
        <x-primary-button :href="$actionHref" wire:navigate>
            {{ $actionLabel }}
        </x-primary-button>
    @endif
    {{ $slot }}
</div>
