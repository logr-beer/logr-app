<div>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">System Info</h1>
            @if (session('message'))
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-400">
                    {{ session('message') }}
                </div>
            @endif

            {{-- Queue / Jobs --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6" wire:poll.5s>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Queue</h3>
                    @if($this->queueStats['pending'] > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Processing
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            Idle
                        </span>
                    @endif
                </div>

                <dl class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->queueStats['pending']) }}</dd>
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pending Jobs</dt>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                        <dd class="text-2xl font-bold {{ $this->queueStats['failed'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ number_format($this->queueStats['failed']) }}</dd>
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mt-1">Failed Jobs</dt>
                    </div>
                </dl>

                @if($this->queueStats['failed'] > 0)
                    <div class="flex gap-2 mb-4">
                        <button wire:click="retryFailedJobs" wire:confirm="Retry all failed jobs?" class="px-3 py-1.5 text-xs font-medium rounded-md text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 dark:text-amber-400 dark:bg-amber-900/20 dark:border-amber-800 dark:hover:bg-amber-900/40 transition-colors">
                            Retry All
                        </button>
                        <button wire:click="flushFailedJobs" wire:confirm="Permanently delete all failed jobs?" class="px-3 py-1.5 text-xs font-medium rounded-md text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 dark:text-red-400 dark:bg-red-900/20 dark:border-red-800 dark:hover:bg-red-900/40 transition-colors">
                            Flush Failed
                        </button>
                    </div>
                @endif

                @if($this->queueStats['batches']->isNotEmpty())
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Batches</h4>
                    <div class="space-y-2">
                        @foreach($this->queueStats['batches'] as $batch)
                            <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg text-sm">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $batch['name'] ?: 'Unnamed batch' }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 ml-2">
                                        {{ $batch['total'] - $batch['pending'] }}/{{ $batch['total'] }} jobs
                                    </span>
                                </div>
                                <div>
                                    @if($batch['failed'] > 0)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">{{ $batch['failed'] }} failed</span>
                                    @elseif($batch['finished'])
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Done</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Running</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Database Stats --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Database</h3>
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($this->stats as $label => $count)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                            <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($count) }}</dd>
                            <dt class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- System Information --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                    @foreach($this->systemInfo as $label => $value)
                        <div class="flex justify-between sm:block">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Updates --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Updates</h3>
                </div>
                <livewire:version-notice />
                <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                    Version checks are cached for 4 hours. Click the refresh icon to check now.
                </div>
            </div>

            {{-- Danger Zone --}}
            @unless(config('app.demo_mode'))
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 border border-red-200 dark:border-red-900/50">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-1">Danger Zone</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">These actions are destructive and cannot be undone.</p>

                    <div class="space-y-4">
                        {{-- Purge Data --}}
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Purge All Data</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Delete all beers, check-ins, inventory, and collections. User accounts are preserved.</p>
                            </div>
                            <button wire:click="confirmPurge" class="shrink-0 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                Purge Data
                            </button>
                        </div>

                        {{-- Purge Settings --}}
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Purge Settings</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Clear all user settings (API keys, integrations, webhooks). Beer data is preserved.</p>
                            </div>
                            <button wire:click="confirmPurgeSettings" class="shrink-0 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                Purge Settings
                            </button>
                        </div>

                        {{-- Re-run Setup Wizard --}}
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Re-run Setup Wizard</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Wipe everything and start fresh. This deletes all data <strong>including user accounts</strong>.</p>
                            </div>
                            <button wire:click="confirmReset" class="shrink-0 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                Reset App
                            </button>
                        </div>
                    </div>
                </div>
            @endunless

            {{-- Links --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resources</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="{{ config('logr.links.github') }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">GitHub</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Source code & issues</div>
                        </div>
                    </a>
                    <a href="{{ config('logr.links.docker_hub') }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M13.983 11.078h2.119a.186.186 0 0 0 .186-.185V9.006a.186.186 0 0 0-.186-.186h-2.119a.186.186 0 0 0-.185.186v1.887c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 0 0 .186-.186V3.574a.186.186 0 0 0-.186-.185h-2.118a.185.185 0 0 0-.185.185v1.888c0 .102.082.186.185.186m0 2.716h2.118a.187.187 0 0 0 .186-.186V6.29a.186.186 0 0 0-.186-.185h-2.118a.185.185 0 0 0-.185.185v1.887c0 .102.082.186.185.186m-2.93 0h2.12a.186.186 0 0 0 .184-.186V6.29a.185.185 0 0 0-.185-.185H8.1a.185.185 0 0 0-.185.185v1.887c0 .102.083.186.185.186m-2.964 0h2.119a.186.186 0 0 0 .185-.186V6.29a.186.186 0 0 0-.185-.185H5.136a.186.186 0 0 0-.186.185v1.887c0 .102.084.186.186.186m5.893 2.715h2.118a.186.186 0 0 0 .186-.185V9.006a.186.186 0 0 0-.186-.186h-2.118a.185.185 0 0 0-.185.186v1.887c0 .102.082.185.185.185m-2.93 0h2.12a.185.185 0 0 0 .184-.185V9.006a.185.185 0 0 0-.184-.186h-2.12a.185.185 0 0 0-.184.186v1.887c0 .102.083.185.185.185m-2.964 0h2.119a.185.185 0 0 0 .185-.185V9.006a.186.186 0 0 0-.185-.186H5.136a.186.186 0 0 0-.186.186v1.887c0 .102.084.185.186.185m-2.92 0h2.12a.185.185 0 0 0 .184-.185V9.006a.186.186 0 0 0-.184-.186h-2.12a.185.185 0 0 0-.184.186v1.887c0 .102.082.185.185.185M23.763 9.89c-.065-.051-.672-.51-1.954-.51-.338.001-.676.03-1.01.087-.248-1.7-1.653-2.53-1.716-2.566l-.344-.199-.226.327c-.284.438-.49.922-.612 1.43-.23.97-.09 1.882.403 2.661-.595.332-1.55.413-1.744.42H.751a.751.751 0 0 0-.75.748 11.376 11.376 0 0 0 .692 4.062c.545 1.428 1.355 2.48 2.41 3.124 1.18.723 3.1 1.137 5.275 1.137.983.003 1.963-.086 2.93-.266a12.248 12.248 0 0 0 3.823-1.389c.98-.567 1.86-1.288 2.61-2.136 1.252-1.418 1.998-2.997 2.553-4.4h.221c1.372 0 2.215-.549 2.68-1.009.309-.293.55-.65.707-1.046l.098-.288Z"/></svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Docker Hub</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Container images</div>
                        </div>
                    </a>
                    <a href="{{ config('logr.links.website') }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.732-3.558"/></svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Author</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ajpenninga.com</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Purge Confirmation Modal --}}
    @if($showPurgeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" wire:click="$set('showPurgeModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Purge All Data</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        This will permanently delete all beers, breweries, check-ins, inventory, collections, tags, and companions. Your user account will be preserved.
                    </p>

                    <div class="mb-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input wire:model="purgeWithDemo" type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Re-load demo data after purging</span>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label for="purgeConfirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Type <strong>PURGE</strong> to confirm
                        </label>
                        <input wire:model="purgeConfirmation" id="purgeConfirmation" type="text" autocomplete="off"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-red-500 focus:border-red-500" />
                        @error('purgeConfirmation') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showPurgeModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                        <button wire:click="purgeData" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Purge Everything
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Purge Settings Confirmation Modal --}}
    @if($showPurgeSettingsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" wire:click="$set('showPurgeSettingsModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Purge Settings</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        This will clear all user settings including API keys, Untappd credentials, Discord webhooks, and other integrations. Your beer data, check-ins, and collections will be preserved.
                    </p>

                    <div class="mb-4">
                        <label for="purgeSettingsConfirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Type <strong>PURGE</strong> to confirm
                        </label>
                        <input wire:model="purgeSettingsConfirmation" id="purgeSettingsConfirmation" type="text" autocomplete="off"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-red-500 focus:border-red-500" />
                        @error('purgeSettingsConfirmation') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showPurgeSettingsModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                        <button wire:click="purgeSettings" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Purge Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reset Confirmation Modal --}}
    @if($showResetModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" wire:click="$set('showResetModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Reset Entire Application</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        This will wipe <strong>everything</strong>, including your user account, and redirect you to the setup wizard.
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400 font-medium mb-4">
                        This action cannot be undone.
                    </p>

                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('showResetModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                        <button wire:click="resetApp" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Reset & Re-run Setup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
