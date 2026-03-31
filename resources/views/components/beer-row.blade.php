@props(['title', 'beers', 'emptyMessage' => 'No beers yet'])

@if($beers->isNotEmpty())
<div class="mb-8">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $title }}</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach($beers as $beer)
            <x-beer-card :beer="$beer" />
        @endforeach
    </div>
</div>
@endif
