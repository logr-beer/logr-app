{{-- Discord Bot (Logr) --}}
@php $demoMode = config('app.demo_mode'); @endphp
@if(config('services.logr.discord_bot_url'))
    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
                <x-icon name="discord" size="4" :solid="true" class="inline-block mr-1 text-amber-400" />
                Discord Bot (Logr)
            </h3>
            <x-env-badge name="LOGR_DISCORD_BOT_URL" />
        </div>

        @foreach($discordBots as $index => $bot)
            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
                <div class="flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bot['guild_name'] ?? 'Unknown Server' }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            @if(!empty($bot['channel_name']))
                                #{{ $bot['channel_name'] }}
                            @else
                                {{ $bot['guild_id'] }}
                            @endif
                        </p>
                    </div>

                    @unless($demoMode)
                        @if(auth()->user()->is_admin)
                            <button type="button" wire:click="loadChannels({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Change channel">
                                <span wire:loading wire:target="loadChannels({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5"/></svg>
                            </button>
                        @endif

                        <button type="button" wire:click="testBotCheckin({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Send a sample check-in">
                            <span wire:loading wire:target="testBotCheckin({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                            Test Check-in
                        </button>
                        <button type="button" wire:click="testBotInventory({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Send a sample inventory notification">
                            <span wire:loading wire:target="testBotInventory({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                            Test Inventory
                        </button>

                        @if(auth()->user()->is_admin)
                            <button type="button" wire:click="disconnectBot({{ $index }})" wire:confirm="Disconnect this Discord server?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                                <x-icon name="trash" size="4" />
                            </button>
                        @endif
                    @endunless
                </div>

                @if(auth()->user()->is_admin && $loadingChannelsFor === $index && !empty($botChannels))
                    <div class="flex items-center gap-2">
                        <select wire:change="changeBotChannel({{ $index }}, $event.target.value)"
                            class="flex-1 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md">
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

                @php $guildId = $bot['guild_id']; @endphp
                <div class="flex items-center gap-4 text-xs">
                    <label class="inline-flex items-center gap-1.5 {{ $demoMode ? '' : 'cursor-pointer' }}">
                        <input type="checkbox" {{ $demoMode ? 'disabled' : '' }} wire:click="toggleBotPref('{{ $guildId }}', 'publish_checkins')" {{ !empty($botPrefs[$guildId]['publish_checkins']) ? 'checked' : '' }}
                            class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                        <span class="text-gray-700 dark:text-gray-300">Check-ins</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 {{ $demoMode ? '' : 'cursor-pointer' }}">
                        <input type="checkbox" {{ $demoMode ? 'disabled' : '' }} wire:click="toggleBotPref('{{ $guildId }}', 'publish_purchases')" {{ !empty($botPrefs[$guildId]['publish_purchases']) ? 'checked' : '' }}
                            class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                        <span class="text-gray-700 dark:text-gray-300">Inventory additions</span>
                    </label>
                </div>
            </div>
        @endforeach

        {{-- Discord Identity --}}
        @if(!empty($discordBots))
            <div class="flex items-center justify-between px-1">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @if($discordUsername)
                        Posting as <span class="font-medium text-gray-700 dark:text-gray-300">{{ $discordUsername }}</span>
                    @else
                        Posting as <span class="font-medium text-gray-700 dark:text-gray-300">Logr</span>
                    @endif
                </div>
                @unless($demoMode)
                    <div>
                        @if($discordUsername)
                            <form action="{{ route('discord.unlink') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors">
                                    Unlink Discord
                                </button>
                            </form>
                        @else
                            <a href="{{ route('discord.link') }}" class="text-xs text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                                Link Discord
                            </a>
                        @endif
                    </div>
                @endunless
            </div>
        @endif

        @unless($demoMode)
            {{-- Connect buttons (admin only) --}}
            @if(auth()->user()->is_admin)
                @if(empty($discordBots))
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 space-y-1.5">
                        <p>To set up the Logr Discord bot:</p>
                        <ol class="list-decimal list-inside space-y-0.5 ml-1">
                            <li>Add the bot to your server using the <strong>"Add Bot to Server"</strong> button below</li>
                            <li>Click <strong>"Connect with Discord"</strong> to link and select a channel</li>
                        </ol>
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    <a href="{{ route('logr.connect') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#5865F2] text-white text-sm font-medium rounded-lg hover:bg-[#4752C4] transition-colors">
                        <x-icon name="discord" size="5" :solid="true" />
                        Connect with Discord
                    </a>
                    <a href="{{ rtrim(config('services.logr.discord_bot_url'), '/') }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <x-icon name="plus" size="4" />
                        Add Bot to Server
                    </a>
                </div>
            @elseif(empty($discordBots))
                <p class="text-sm text-gray-500 dark:text-gray-400">No Discord bot has been connected yet. Ask an admin to set it up.</p>
            @endif
        @endunless
    </div>
@endif
