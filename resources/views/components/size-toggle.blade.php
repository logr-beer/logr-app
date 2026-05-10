@props([
    'section',
    'current',
    'sizes' => [5, 10, 25],
])

<div class="flex items-center gap-1">
    @foreach($sizes as $size)
        <button wire:click="expandSection('{{ $section }}', {{ $size }})" class="px-2 py-1 rounded text-xs font-medium {{ $current === $size ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-500' }}">{{ $size }}</button>
    @endforeach
</div>
