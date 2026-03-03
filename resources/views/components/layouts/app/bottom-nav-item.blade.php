@props(['href', 'icon', 'active' => false, 'accent' => false])

<a href="{{ $href }}"
   @class([
       'flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition',
       'text-primary-600' => $active && ! $accent,
       'text-gray-400' => ! $active && ! $accent,
       'text-primary-600' => $accent,
   ])>
    @if($accent && ! $active)
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-600 text-white">
            <x-dynamic-component :component="'icons.' . $icon" class="h-5 w-5" />
        </span>
    @else
        <x-dynamic-component :component="'icons.' . $icon" class="h-5 w-5" />
    @endif
    <span>{{ $slot }}</span>
</a>
