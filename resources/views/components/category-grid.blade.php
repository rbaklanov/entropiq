@props([
    'categories',
    'selected' => 0,
])

<div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
    @foreach($categories as $category)
        @php $isSelected = $selected === $category->id; @endphp
        <button
            type="button"
            wire:click="selectCategory({{ $category->id }})"
            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
            @class([
                'flex min-h-24 flex-col items-center justify-center gap-2 rounded-md border p-3 transition-colors',
                'border-gray-900 bg-gray-900 text-white' => $isSelected,
                'border-gray-200 bg-white text-gray-900 hover:border-gray-900' => ! $isSelected,
            ])
        >
            <x-category-icon :icon="$category->icon" size="md" :inverse="$isSelected" />
            <span class="text-center text-small leading-tight">
                {{ $category->localizedName() }}
            </span>
        </button>
    @endforeach
</div>
