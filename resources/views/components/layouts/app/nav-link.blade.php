@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}"
   @if($active) aria-current="page" @endif
   @class([
       'flex min-h-11 items-center gap-3 rounded-md px-3 py-2 text-[15px] transition-colors',
       'bg-gray-100 font-semibold text-gray-900' => $active,
       'text-gray-500 hover:bg-gray-100 hover:text-gray-900' => ! $active,
   ])>
    <x-dynamic-component :component="'icons.' . $icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
