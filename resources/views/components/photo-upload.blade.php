@props([
    'wireModel',
    'multiple' => false,
    'label' => 'Photos',
    'hint' => null,
    'error' => null,
    'previews' => null,
    'existingPhotos' => null,
    'removeAction' => null,
])

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
    <div class="flex items-start gap-4">
        {{-- Preview area --}}
        <div class="flex flex-wrap gap-2 flex-shrink-0">
            @if($previews && count($previews))
                @foreach($previews as $index => $photo)
                    <div class="relative group">
                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-20 h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                        @if($removeAction)
                            <button
                                type="button"
                                wire:click="{{ $removeAction }}({{ $index }})"
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity shadow-sm"
                            >&times;</button>
                        @endif
                    </div>
                @endforeach
            @elseif($existingPhotos && count($existingPhotos))
                @foreach($existingPhotos as $photo)
                    <div class="w-20 h-24 rounded-lg overflow-hidden flex-shrink-0">
                        <img src="{{ str_starts_with($photo, 'http') ? $photo : Storage::url($photo) }}" alt="Photo" class="w-full h-full object-cover" />
                    </div>
                @endforeach
            @else
                <div class="w-20 h-24 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                    <x-icon name="image" size="8" class="text-gray-400" />
                </div>
            @endif
        </div>

        {{-- Upload input --}}
        <div class="flex-1 min-w-0">
            <input
                wire:model="{{ $wireModel }}"
                type="file"
                {{ $multiple ? 'multiple' : '' }}
                accept="image/*"
                class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-600 file:text-white hover:file:bg-amber-700 file:cursor-pointer file:transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 rounded-lg"
            />
            @if($hint)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            @endif
            @if($error)
                @error($error) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            @endif
            <div wire:loading wire:target="{{ $wireModel }}" class="mt-1 text-sm text-amber-600">
                Uploading...
            </div>
        </div>
    </div>
</div>
