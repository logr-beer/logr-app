<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $geocodingEnabled = false;

    public function mount(): void
    {
        $this->geocodingEnabled = (bool) Auth::user()->getData('geocoding_enabled', false);
    }

    public function save(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        Auth::user()->setData('geocoding_enabled', $this->geocodingEnabled);
        Auth::user()->save();

        $this->dispatch('saved');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Preferences
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage how Logr handles your data.
        </p>
    </header>

    <form wire:submit="save" class="mt-6 space-y-4">
        <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
            <input wire:model="geocodingEnabled" type="checkbox" {{ config('app.demo_mode') ? 'disabled' : '' }}
                class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable location geocoding</span>
            <span></span>
            <p class="text-xs text-gray-400 dark:text-gray-500">Automatically look up coordinates for breweries and venues using OpenStreetMap's Nominatim API. This sends city/state data to an external service to display locations on maps.</p>
        </label>

        @unless(config('app.demo_mode'))
            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>

                <x-action-message class="me-3" on="saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        @endunless
    </form>
</section>
