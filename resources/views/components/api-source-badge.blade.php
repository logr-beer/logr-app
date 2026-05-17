@props(['source'])

@php
    $labels = [
        'pub' => 'LogrDB',
        'catalog' => 'Catalog.beer',
        'untappd' => 'Untappd',
        'openbrewerydb' => 'Open Brewery DB',
    ];
    $label = $labels[$source] ?? $source;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400']) }}>
    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-3.75"/></svg>
    {{ $label }}
</span>
