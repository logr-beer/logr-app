<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Your Check-ins</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('checkins.export') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export CSV
            </a>
            <button
                wire:click="toggleSelecting"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $selecting ? 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                {{ $selecting ? 'Cancel' : 'Select' }}
            </button>
        </div>
    </div>

    @if($checkins->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No check-ins yet</h3>
            <p class="text-gray-500 dark:text-gray-400">Check in to a beer to start tracking your tastings.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($checkins as $checkin)
                <div
                    @if($selecting) wire:click="toggleSelected({{ $checkin->id }})" @endif
                    class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm {{ $selecting ? 'cursor-pointer' : '' }} {{ $selecting && in_array($checkin->id, $selected) ? 'ring-2 ring-amber-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}"
                >
                    <div class="flex items-start gap-4">
                        {{-- Checkbox when selecting --}}
                        @if($selecting)
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center {{ in_array($checkin->id, $selected) ? 'bg-amber-500 border-amber-500 text-white' : 'border-gray-400 dark:border-gray-500' }}">
                                    @if(in_array($checkin->id, $selected))
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Beer photo --}}
                        <a href="{{ route('beers.show', $checkin->beer) }}" wire:navigate class="flex-shrink-0 {{ $selecting ? 'pointer-events-none' : '' }}">
                            <div class="w-16 h-20 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                @if($checkin->beer->photo_path)
                                    <img src="{{ Storage::url($checkin->beer->photo_path) }}" alt="{{ $checkin->beer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                                    </div>
                                @endif
                            </div>
                        </a>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <a href="{{ route('beers.show', $checkin->beer) }}" wire:navigate class="font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition-colors {{ $selecting ? 'pointer-events-none' : '' }}">
                                        {{ $checkin->beer->name }}
                                    </a>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $checkin->beer->brewery?->name ?? 'Unknown Brewery' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <time class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap" datetime="{{ $checkin->created_at->toISOString() }}">
                                        {{ $checkin->created_at->diffForHumans() }}
                                    </time>
                                    @if($checkin->untappd_id && str_starts_with($checkin->untappd_id, 'http'))
                                        <a href="{{ $checkin->untappd_id }}" target="_blank" rel="noopener" class="text-amber-400 hover:text-amber-500" title="View on Untappd">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        </a>
                                    @endif
                                    @if(!$selecting)
                                        <a href="{{ route('checkins.edit', $checkin) }}" wire:navigate class="text-gray-400 hover:text-amber-500 dark:text-gray-500 dark:hover:text-amber-400 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Rating Stars --}}
                            @if($checkin->rating !== null)
                                <div class="flex items-center gap-0.5 mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $checkin->rating)
                                            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>
                                        @endif
                                    @endfor
                                    <span class="ml-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ number_format($checkin->rating, 1) }}</span>
                                </div>
                            @endif

                            {{-- Serving Type Badge --}}
                            @if($checkin->serving_type)
                                <span class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    @switch($checkin->serving_type)
                                        @case('draft')
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636"/></svg>
                                            @break
                                        @case('bottle')
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5m0 0v6.75h14V14.5l-4.091-4.091a2.25 2.25 0 0 1-.659-1.591V3.104"/></svg>
                                            @break
                                        @case('can')
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5m-10.5 0v9a2.25 2.25 0 0 0 2.25 2.25h6a2.25 2.25 0 0 0 2.25-2.25v-9m-10.5 0V6a2.25 2.25 0 0 1 2.25-2.25h6A2.25 2.25 0 0 1 17.25 6v1.5"/></svg>
                                            @break
                                    @endswitch
                                    {{ ucfirst($checkin->serving_type) }}
                                </span>
                            @endif

                            {{-- Location / Venue --}}
                            @if($checkin->venue || $checkin->location)
                                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                    {{ $checkin->venue?->name ?? $checkin->location }}
                                </p>
                            @endif

                            {{-- Notes --}}
                            @if($checkin->notes)
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $checkin->notes }}</p>
                            @endif

                            {{-- Photos --}}
                            @if($checkin->photos->isNotEmpty())
                                <div class="flex gap-2 mt-3 overflow-x-auto">
                                    @foreach($checkin->photos as $photo)
                                        <img src="{{ Storage::url($photo->photo_path) }}" alt="Check-in photo" class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 {{ $selecting ? 'mb-20' : '' }}">
            {{ $checkins->links() }}
        </div>
    @endif

    {{-- Floating Action Bar (visible whenever in select mode) --}}
    @if($selecting)
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 dark:bg-gray-700 text-white rounded-xl shadow-2xl px-5 py-3 flex items-center gap-3 max-w-[95vw]">
            <span class="text-sm font-medium whitespace-nowrap">{{ count($selected) }} selected</span>

            <div class="w-px h-6 bg-gray-600"></div>

            <button wire:click="selectAll" class="text-sm text-amber-400 hover:text-amber-300 whitespace-nowrap">All</button>
            @if(count($selected) > 0)
                <button wire:click="deselectAll" class="text-sm text-gray-400 hover:text-gray-200 whitespace-nowrap">None</button>
            @endif

            @if(count($selected) > 0)
                <div class="w-px h-6 bg-gray-600"></div>

                {{-- Delete --}}
                <button
                    wire:click="deleteSelected"
                    wire:confirm="Delete {{ count($selected) }} check-in(s)? This cannot be undone."
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    Delete
                </button>
            @endif

            <button wire:click="toggleSelecting" class="text-sm text-gray-400 hover:text-white transition-colors ml-1">Cancel</button>
        </div>
    @endif
</div>
