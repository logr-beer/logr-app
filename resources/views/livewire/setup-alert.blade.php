<div>
    @if(! $dismissed && count($missing) > 0)
        <div class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-sm text-blue-700 dark:text-blue-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                        <span>Finish setting up your integrations:</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 pl-6 sm:pl-0">
                        @if(in_array('untappd', $missing))
                            <a href="{{ route('admin.api') }}" wire:navigate class="font-medium underline hover:text-blue-800 dark:hover:text-blue-300">Connect Untappd RSS</a>
                            @if(in_array('discord', $missing))
                                <span class="hidden sm:inline">·</span>
                            @endif
                        @endif
                        @if(in_array('discord', $missing))
                            <a href="{{ route('admin.notifications') }}" wire:navigate class="font-medium underline hover:text-blue-800 dark:hover:text-blue-300">Set up Discord notifications</a>
                        @endif
                    </div>
                </div>
                <button wire:click="dismiss" class="shrink-0 p-1 text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    @endif
</div>
