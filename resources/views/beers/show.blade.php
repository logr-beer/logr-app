@php $beer = App\Models\Beer::findOrFail(request()->route('beer')); @endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:beer-show :beer="$beer" />
        </div>
    </div>
</x-app-layout>
