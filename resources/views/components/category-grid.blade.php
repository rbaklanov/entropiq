@props([
    'categories',
    'selected' => 0,
])

<div class="grid grid-cols-4 gap-2 sm:grid-cols-5">
    @foreach($categories as $category)
        <button
            type="button"
            wire:click="selectCategory({{ $category->id }})"
            @class([
                'flex flex-col items-center gap-1 rounded-xl p-3 transition-all duration-200',
                'bg-primary-50 ring-2 ring-primary-500' => $selected === $category->id,
                'bg-white hover:bg-gray-50' => $selected !== $category->id,
            ])
        >
            <x-category-icon :icon="$category->icon" :color="$category->color" size="md" />
            <span class="text-center text-small leading-tight text-gray-700">
                {{ $category->localizedName() }}
            </span>
        </button>
    @endforeach
</div>
