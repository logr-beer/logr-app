<div>
    @if(! $dismissed && count($missing) > 0)
        <div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-sm text-amber-700 dark:text-amber-400">
                    <div class="flex items-center gap-2">
                        <x-icon name="info" size="4" class="shrink-0" />
                        <span>Finish setting up your integrations:</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 pl-6 sm:pl-0">
                        @if(in_array('untappd', $missing))
                            <a href="{{ route('admin.api') }}" wire:navigate class="font-medium underline hover:text-amber-800 dark:hover:text-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-amber-50 dark:focus:ring-offset-amber-950 rounded-sm">Connect Untappd RSS</a>
                            @if(in_array('discord', $missing))
                                <span class="hidden sm:inline">·</span>
                            @endif
                        @endif
                        @if(in_array('discord', $missing))
                            <a href="{{ route('admin.notifications') }}" wire:navigate class="font-medium underline hover:text-amber-800 dark:hover:text-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-amber-50 dark:focus:ring-offset-amber-950 rounded-sm">Set up notifications</a>
                        @endif
                    </div>
                </div>
                <button wire:click="dismiss" class="shrink-0 p-1 text-amber-400 hover:text-amber-600 dark:hover:text-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-amber-50 dark:focus:ring-offset-amber-950 rounded-sm transition-colors">
                    <x-icon name="x-mark" size="4" />
                </button>
            </div>
        </div>
    @endif
</div>
