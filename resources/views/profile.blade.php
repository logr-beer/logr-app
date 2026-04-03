<x-app-layout>
    <x-slot name="title">Profile</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile</h1>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <livewire:profile.update-profile-information-form />
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <livewire:profile.preferences-form />
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Export Data') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Download your data as CSV files.') }}
                            </p>
                        </header>

                        <div class="mt-6 space-y-3">
                            <a href="{{ route('beers.export') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors w-full justify-center sm:w-auto sm:justify-start">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                Export Beers
                            </a>
                            <a href="{{ route('checkins.export') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors w-full justify-center sm:w-auto sm:justify-start">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                Export Check-Ins
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-lg">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
