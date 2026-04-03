{{-- Discord Bot (Logr) --}}
@if(config('services.logr.discord_url'))
    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
                <svg class="w-4 h-4 inline-block mr-1 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                Discord Bot (Logr)
            </h3>
            <x-env-badge name="LOGR_DISCORD_URL" />
        </div>
        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-xs text-indigo-700 dark:text-indigo-400 space-y-1.5">
            <p>To set up the Logr Discord bot:</p>
            <ol class="list-decimal list-inside space-y-0.5 ml-1">
                <li>A server admin must first add the bot using the <strong>"Add Bot to Server"</strong> button below</li>
                <li>Once added, click <strong>"Connect with Discord"</strong> to link your Logr account</li>
                <li>Select which channel the bot should post to</li>
            </ol>
            <p class="pt-1">The bot will automatically post check-ins and inventory updates to your chosen channel.</p>
        </div>

        @foreach($discordBots as $index => $bot)
            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
                <div class="flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bot['label'] ?? $bot['guild_name'] ?? 'Unknown Server' }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $bot['guild_name'] ?? $bot['guild_id'] }}
                            @if(!empty($bot['channel_name']))
                                &middot; #{{ $bot['channel_name'] }}
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="loadChannels({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Change channel">
                        <span wire:loading wire:target="loadChannels({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5"/></svg>
                    </button>
                    <button type="button" wire:click="testBot({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors">
                        <span wire:loading wire:target="testBot({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        Test
                    </button>
                    <button type="button" wire:click="removeBot({{ $index }})" wire:confirm="Disconnect this server?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                </div>

                @if($loadingChannelsFor === $index && !empty($botChannels))
                    <div class="flex items-center gap-2">
                        <select wire:change="changeBotChannel({{ $index }}, $event.target.value)"
                            class="flex-1 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md">
                            <option value="">Select channel...</option>
                            @foreach($botChannels as $channel)
                                <option value="{{ $channel['id'] }}">
                                    #{{ $channel['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="cancelChannelPicker" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Cancel</button>
                    </div>
                @endif

                <div class="flex items-center gap-4 text-xs">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" wire:click="toggleBotSetting({{ $index }}, 'publish_checkins')" {{ !empty($bot['publish_checkins']) ? 'checked' : '' }}
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                        <span class="text-gray-700 dark:text-gray-300">Check-ins</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" wire:click="toggleBotSetting({{ $index }}, 'publish_purchases')" {{ !empty($bot['publish_purchases']) ? 'checked' : '' }}
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                        <span class="text-gray-700 dark:text-gray-300">Inventory additions</span>
                    </label>
                </div>
            </div>
        @endforeach

        <div class="flex items-center gap-3">
            <a href="{{ route('logr.connect') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#5865F2] text-white text-sm font-medium rounded-lg hover:bg-[#4752C4] transition-colors">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                Connect with Discord
            </a>
            <a href="{{ rtrim(config('services.logr.discord_url'), '/') }}" target="_blank"
                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Bot to Server
            </a>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">A server admin must add the bot first, then any member can connect.</p>
    </div>
@endif
