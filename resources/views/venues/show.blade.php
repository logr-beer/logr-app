@php $venue = App\Models\Venue::findOrFail(request()->route('venue')); @endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:venue-show :venue="$venue" />
        </div>
    </div>
</x-app-layout>
