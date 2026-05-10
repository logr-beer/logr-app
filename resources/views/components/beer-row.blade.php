@props(['title', 'beers', 'dateField' => null, 'dateLabel' => null, 'emptyMessage' => 'No beers yet'])

@if($beers->isNotEmpty())
<div class="mb-8">
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $title }}</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($beers as $beer)
            <x-beer-card
                :beer="$beer"
                :date="$dateField ? ($beer->{$dateField} ? \Carbon\Carbon::parse($beer->{$dateField}) : null) : null"
                :dateLabel="$dateLabel"
            />
        @endforeach
    </div>
</div>
@endif
