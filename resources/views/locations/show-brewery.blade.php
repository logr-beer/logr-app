@php $location = App\Models\Brewery::findOrFail(request()->route('brewery')); @endphp

<x-app-layout>
    <x-slot name="title">{{ $location->name }} | Breweries</x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:location-show :location="$location" />
        </div>
    </div>
</x-app-layout>
