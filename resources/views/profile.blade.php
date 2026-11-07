<x-app-layout>
    <x-slot name="title">Profile</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile</h1>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <livewire:profile.update-profile-information-form />
                </div>

                @unless(config('app.demo_mode'))
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                        <livewire:profile.update-password-form />
                    </div>
                @endunless
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <livewire:profile.preferences-form />
                </div>

                @unless(config('app.demo_mode'))
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div class="max-w-xl">
                            <header>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('Export Data') }}
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Download all your data as a JSON file.') }}
                                </p>
                            </header>

                            <div class="mt-6">
                                <x-primary-button :href="route('export')" class="w-full justify-center sm:w-auto sm:justify-start">
                                    <x-icon name="download" size="4" />
                                    Export All Data
                                </x-primary-button>
                            </div>
                        </div>
                    </div>
                @endunless

                @unless(config('app.demo_mode'))
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                        <livewire:profile.delete-user-form />
                    </div>
                @endunless

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('About Logr') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Logr is self-hosted and open source.') }}
                            </p>
                        </header>

                        <dl class="mt-6 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Version') }}</dt>
                                <dd>
                                    <a href="{{ config('logr.links.github') }}/releases/tag/v{{ config('logr.version') }}" target="_blank" rel="noopener" class="font-mono text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 rounded-sm transition-colors">v{{ config('logr.version') }}</a>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Source code') }}</dt>
                                <dd>
                                    <a href="{{ config('logr.links.github') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 rounded-sm transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                                        {{ str_replace('https://', '', config('logr.links.github')) }}
                                    </a>
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
