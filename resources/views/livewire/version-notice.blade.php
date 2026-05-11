<div class="inline-flex items-center gap-1">
    @if($updateAvailable)
        <span class="mx-1">&middot;</span>
        <a href="{{ $releaseUrl }}" target="_blank" rel="noopener" class="text-amber-600 dark:text-amber-400 hover:text-amber-500 dark:hover:text-amber-300 font-medium transition-colors">
            Update available (v{{ $latestVersion }})
        </a>
    @endif
    <button wire:click="checkNow" wire:loading.attr="disabled" title="Check for updates" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
        <x-icon name="refresh" size="3.5" wire:loading.class="animate-spin" wire:target="checkNow" />
    </button>
</div>
