@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'optional' => false,
    'span' => null,
])

<div @class([
    'md:col-span-2' => $span === 'full',
])>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}{{ $required ? ' *' : '' }}
            @if($optional) <span class="text-gray-400">(optional)</span> @endif
        </label>
    @endif
    {{ $slot }}
    @if($name)
        @error($name) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
    @endif
</div>
