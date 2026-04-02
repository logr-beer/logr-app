<div>
    @if($updateAvailable)
        <span class="mx-1">&middot;</span>
        <a href="{{ $releaseUrl }}" target="_blank" rel="noopener" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-500 dark:hover:text-yellow-300 font-medium transition-colors">
            Update available (v{{ $latestVersion }})
        </a>
    @endif
</div>
