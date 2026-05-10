@props([
    'padding' => '6',
])

<div {{ $attributes->merge(['class' => "bg-white dark:bg-gray-800 rounded-xl shadow-sm p-{$padding}"]) }}>
    {{ $slot }}
</div>
