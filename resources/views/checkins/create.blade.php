<x-app-layout>
    <x-slot name="title">New Check-in | Check-ins</x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:checkin-form :beer="request()->query('beer')" />
        </div>
    </div>
</x-app-layout>
