<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $geocodingEnabled = false;
    public bool $shareCheckinData = false;

    public function mount(): void
    {
        $this->geocodingEnabled = (bool) Auth::user()->getData('geocoding_enabled', false);
        $this->shareCheckinData = (bool) Auth::user()->getData('share_checkin_data', false);
    }

    public function save(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        Auth::user()->setData('geocoding_enabled', $this->geocodingEnabled);
        Auth::user()->setData('share_checkin_data', $this->shareCheckinData);
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
        <div>
            <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                <input wire:model="geocodingEnabled" type="checkbox" {{ config('app.demo_mode') ? 'disabled' : '' }}
                    class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable location geocoding</span>
                <span></span>
                <p class="text-xs text-gray-500 dark:text-gray-400">Automatically look up coordinates for breweries and venues using OpenStreetMap's Nominatim API. This sends city/state data to an external service to display locations on maps.</p>
                <span></span>
                <div x-data="{ open: false }" class="mt-0.5">
                    <button type="button" @click="open = !open" class="text-xs text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                        <span x-text="open ? 'Hide example payload' : 'Show example payload'"></span>
                    </button>
                    <pre x-show="open" x-collapse class="mt-1.5 p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-400 font-mono overflow-x-auto">{
  "query": "Bell's Brewery, Comstock, MI",
  "format": "json"
}</pre>
                </div>
            </label>
        </div>

        <div>
            <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                <input wire:model="shareCheckinData" type="checkbox" {{ config('app.demo_mode') ? 'disabled' : '' }}
                    class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Share anonymous check-in data</span>
                <span></span>
                <p class="text-xs text-gray-500 dark:text-gray-400">When you check in a beer, share the beer name, brewery, style, ABV, IBU, rating, and serving type with the Logr community. No personal information, reviews, or venue data is shared.</p>
                <span></span>
                <div x-data="{ open: false }" class="mt-0.5">
                    <button type="button" @click="open = !open" class="text-xs text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                        <span x-text="open ? 'Hide example payload' : 'Show example payload'"></span>
                    </button>
                    <pre x-show="open" x-collapse class="mt-1.5 p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-400 font-mono overflow-x-auto">{
  "beer_name": "Two Hearted Ale",
  "brewery": "Bell's Brewery",
  "style": "American IPA",
  "abv": 7.0,
  "ibu": 55,
  "rating": 4.5,
  "serving_type": "draft",
  "catalog_beer_id": "abc123",
  "checked_in_at": "2026-05-11T18:30:00+00:00"
}</pre>
                </div>
            </label>
        </div>

        @unless(config('app.demo_mode'))
            <div class="flex items-center gap-4">
                <x-primary-button><x-icon name="check" size="4" /> {{ __('Save') }}</x-primary-button>

                <x-action-message class="me-3" on="saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        @endunless
    </form>
</section>
