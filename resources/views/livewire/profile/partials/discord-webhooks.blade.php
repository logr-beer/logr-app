{{-- Discord Webhooks (direct) --}}
@php $demoMode = config('app.demo_mode'); @endphp
<div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
    <div class="flex items-center justify-between">
        <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Webhooks</h4>
        @if(config('services.discord.webhooks'))
            <x-env-badge name="DISCORD_WEBHOOKS" />
        @endif
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        Simple to set up with no external bot required. Works well for single-user setups or quick integrations. Posts directly to a channel using a Discord webhook URL.
    </p>
    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 space-y-1.5">
        <p>To get a Discord webhook URL:</p>
        <ol class="list-decimal list-inside space-y-0.5 ml-1">
            <li>Open your Discord server and go to <strong>Server Settings</strong></li>
            <li>Navigate to <strong>Integrations</strong> &rarr; <strong>Webhooks</strong></li>
            <li>Click <strong>New Webhook</strong>, choose a channel, and copy the webhook URL</li>
        </ol>
        <p class="pt-1">Paste the webhook URL and add a label to identify the channel (e.g. <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded">#beer-log</code>).</p>
    </div>

    @foreach($discordWebhooks as $index => $webhook)
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
            <div class="flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $webhook['label'] ?? 'Webhook' }}</span>
                    @if(!empty($webhook['channel_id']) && !empty($webhook['guild_id']))
                        <p class="text-xs text-gray-500 dark:text-gray-400">Channel: <a href="https://discord.com/channels/{{ $webhook['guild_id'] }}/{{ $webhook['channel_id'] }}" target="_blank" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">{{ $webhook['channel_id'] }}</a></p>
                    @elseif(!empty($webhook['channel_id']))
                        <p class="text-xs text-gray-500 dark:text-gray-400">Channel: {{ $webhook['channel_id'] }}</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $webhook['url'] }}</p>
                    @endif
                </div>
                @unless($demoMode)
                    <button type="button" wire:click="testCheckin({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Send a sample check-in">
                        <span wire:loading wire:target="testCheckin({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        Test Check-in
                    </button>
                    <button type="button" wire:click="testInventory({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Send a sample inventory notification">
                        <span wire:loading wire:target="testInventory({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        Test Inventory
                    </button>
                    <button type="button" wire:click="removeWebhook({{ $index }})" wire:confirm="Remove this webhook?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                        <x-icon name="trash" size="4" />
                    </button>
                @endunless
            </div>
            <div class="flex items-center gap-4 text-xs">
                <label class="inline-flex items-center gap-1.5 {{ $demoMode ? '' : 'cursor-pointer' }}">
                    <input type="checkbox" {{ $demoMode ? 'disabled' : '' }} wire:click="toggleWebhookSetting({{ $index }}, 'publish_checkins')" {{ !empty($webhook['publish_checkins']) ? 'checked' : '' }}
                        class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                    <span class="text-gray-700 dark:text-gray-300">Share check-ins</span>
                </label>
                <label class="inline-flex items-center gap-1.5 {{ $demoMode ? '' : 'cursor-pointer' }}">
                    <input type="checkbox" {{ $demoMode ? 'disabled' : '' }} wire:click="toggleWebhookSetting({{ $index }}, 'publish_purchases')" {{ !empty($webhook['publish_purchases']) ? 'checked' : '' }}
                        class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                    <span class="text-gray-700 dark:text-gray-300">Share inventory</span>
                </label>
            </div>
        </div>
    @endforeach

    @unless($demoMode)
        <div class="space-y-2">
            <div class="flex items-end gap-2">
                <div class="w-36">
                    <x-input-label for="newWebhookLabel" value="Label" />
                    <input wire:model="newWebhookLabel" id="newWebhookLabel" type="text" placeholder="e.g. #beer-log"
                        class="mt-1 block w-full px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-lg shadow-sm" />
                </div>
                <div class="flex-1">
                    <x-input-label for="newWebhookUrl" value="Webhook URL" />
                    <input wire:model="newWebhookUrl" id="newWebhookUrl" type="url" placeholder="https://discord.com/api/webhooks/..."
                        class="mt-1 block w-full px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-lg shadow-sm" />
                </div>
                <x-primary-button type="button" wire:click="addWebhook" class="shrink-0">
                    <span wire:loading wire:target="addWebhook"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                    <x-icon name="plus" size="4" wire:loading.remove wire:target="addWebhook" /> Add
                </x-primary-button>
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('newWebhookUrl')" />
        </div>
    @endunless
</div>
