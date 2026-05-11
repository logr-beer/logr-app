<div class="inline-flex items-center gap-1">
    @if($updateAvailable)
        <span class="mx-1">&middot;</span>
        <a href="{{ $releaseUrl }}" target="_blank" rel="noopener" class="text-amber-600 dark:text-amber-400 hover:text-amber-500 dark:hover:text-amber-300 font-medium transition-colors">
            Update available (v{{ $latestVersion }})
        </a>
    @endif
</div>
