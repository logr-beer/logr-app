<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        Home
                    </x-nav-link>
                    <x-nav-link :href="route('beers.index')" :active="request()->routeIs('beers.*')" wire:navigate>
                        Beers
                    </x-nav-link>
                    <x-nav-link :href="route('collections.index')" :active="request()->routeIs('collections.*')" wire:navigate>
                        Collections
                    </x-nav-link>
                    <x-nav-link :href="route('checkins.index')" :active="request()->routeIs('checkins.*')" wire:navigate>
                        Check-ins
                    </x-nav-link>
                    <x-nav-link :href="route('locations')" :active="request()->routeIs('locations') || request()->routeIs('venues.*')" wire:navigate>
                        Locations
                    </x-nav-link>
                    <x-nav-link :href="route('rankings')" :active="request()->routeIs('rankings')" wire:navigate>
                        Rankings
                    </x-nav-link>
                </div>
            </div>

            <!-- Add Beer + Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <a href="{{ route('checkins.create') }}" wire:navigate class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 transition">
                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Check In
                </a>
                <a href="{{ route('beers.create') }}" wire:navigate class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-amber-500 hover:bg-amber-600 transition">
                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Beer
                </a>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('import')" wire:navigate>
                            {{ __('Import') }}
                        </x-dropdown-link>

                        <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('admin.api')" wire:navigate>
                            {{ __('API Settings') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('admin.notifications')" wire:navigate>
                            {{ __('Notifications') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                Home
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('beers.index')" :active="request()->routeIs('beers.*')" wire:navigate>
                Beers
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('collections.index')" :active="request()->routeIs('collections.*')" wire:navigate>
                Collections
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('checkins.index')" :active="request()->routeIs('checkins.*')" wire:navigate>
                Check-ins
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('locations')" :active="request()->routeIs('locations') || request()->routeIs('venues.*')" wire:navigate>
                Locations
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('rankings')" :active="request()->routeIs('rankings')" wire:navigate>
                Rankings
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('checkins.create')" :active="request()->routeIs('checkins.create')" wire:navigate>
                + Check In
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('beers.create')" :active="request()->routeIs('beers.create')" wire:navigate>
                + Add Beer
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->username }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('import')" wire:navigate>
                    {{ __('Import') }}
                </x-responsive-nav-link>

                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.api')" wire:navigate>
                    {{ __('API Settings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.notifications')" wire:navigate>
                    {{ __('Notifications') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
