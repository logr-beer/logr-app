@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-2 py-1.5 text-sm font-medium text-gray-900 dark:text-gray-100 bg-amber-50 dark:bg-amber-900/20 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-2 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
