@props(['active'])

@php
$base = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset transition duration-150 ease-in-out';

$classes = ($active ?? false)
            ? $base . ' border-amber-500 dark:border-amber-400 text-gray-900 dark:text-gray-100'
            : $base . ' border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
