@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}"
   @class([
       'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
       'bg-primary-50 text-primary-700' => $active,
       'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
   ])>
    <x-dynamic-component :component="'icons.' . $icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
