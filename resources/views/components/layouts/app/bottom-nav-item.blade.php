@props(['href', 'icon', 'active' => false, 'accent' => false])

<a href="{{ $href }}"
   @if($active) aria-current="page" @endif
   @class([
       'flex min-h-14 min-w-14 flex-col items-center justify-center gap-0.5 text-[11px] transition-colors',
       'font-semibold text-gray-900' => $active && ! $accent,
       'text-gray-500' => ! $active && ! $accent,
       'text-primary-600' => $accent,
   ])>
    @if($accent && ! $active)
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-600 text-white">
            <x-dynamic-component :component="'icons.' . $icon" class="h-6 w-6" />
        </span>
        <span class="sr-only">{{ $slot }}</span>
    @else
        <x-dynamic-component :component="'icons.' . $icon" class="h-5 w-5" />
        <span>{{ $slot }}</span>
    @endif
</a>
