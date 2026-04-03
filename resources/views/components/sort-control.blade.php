@props(['options', 'sortField' => 'sortBy', 'directionField' => 'sortDirection'])

<div class="inline-flex items-stretch flex-shrink-0">
    <select
        wire:model.live="{{ $sortField }}"
        class="pl-3 pr-8 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-l-lg border-r-0 text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 appearance-none bg-[length:16px_16px] bg-[right_0.5rem_center] bg-no-repeat"
        style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E&quot;)"
    >
        @foreach($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    <button
        wire:click="$set('{{ $directionField }}', '{{ $this->{$directionField} === 'asc' ? 'desc' : 'asc' }}')"
        class="inline-flex items-center px-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500 dark:text-gray-400 hover:text-amber-500 transition-colors"
        title="{{ $this->{$directionField} === 'asc' ? 'Ascending' : 'Descending' }}"
    >
        @if($this->{$directionField} === 'asc')
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3"/></svg>
        @endif
    </button>
</div>
