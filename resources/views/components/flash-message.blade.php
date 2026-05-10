@props([
    'type' => 'success',
])

@php
$styles = [
    'success' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    'error' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
];
@endphp

@if(session()->has('message'))
    <div class="mb-4 p-4 {{ $styles['success'] }} rounded-lg text-sm">
        {{ session('message') }}
    </div>
@endif

@if(session()->has('error'))
    <div class="mb-4 p-4 {{ $styles['error'] }} rounded-lg text-sm">
        {{ session('error') }}
    </div>
@endif
