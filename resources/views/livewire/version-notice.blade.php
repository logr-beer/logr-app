<div class="inline-flex items-center gap-1">
    @if($updateAvailable)
        <span class="mx-1">&middot;</span>
        <a href="{{ $releaseUrl }}" target="_blank" rel="noopener" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-500 dark:hover:text-yellow-300 font-medium transition-colors">
            Update available (v{{ $latestVersion }})
        </a>
    @endif
    <button wire:click="checkNow" wire:loading.attr="disabled" title="Check for updates" class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
        <svg wire:loading.class="animate-spin" wire:target="checkNow" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
        </svg>
    </button>
</div>
