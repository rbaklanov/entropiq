@props(['transaction'])

@php
    $isExpense = $transaction->type->value === 'expense';
@endphp

<div
    x-data="{ showActions: false, startX: 0, deltaX: 0 }"
    x-on:touchstart="startX = $event.touches[0].clientX; deltaX = 0"
    x-on:touchmove="deltaX = $event.touches[0].clientX - startX"
    x-on:touchend="showActions = deltaX < -60; deltaX = 0"
    x-on:click.away="showActions = false"
    class="relative overflow-hidden"
>
    <div
        class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 transition-transform duration-200"
        :class="showActions ? '-translate-x-20' : 'translate-x-0'"
    >
        <x-category-icon
            :icon="$transaction->category?->icon ?? '📦'"
            :color="$transaction->category?->color ?? '#6366F1'"
            size="md"
        />

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-gray-900">
                {{ $transaction->category?->name['ru'] ?? __('transactions.category') }}
            </p>
            @if($transaction->comment)
                <p class="truncate text-small text-gray-500">{{ $transaction->comment }}</p>
            @endif
        </div>

        <div class="text-right">
            <x-money-display
                :amount="$transaction->amount"
                :type="$transaction->type->value"
                :showSign="true"
                size="sm"
            />
            <p class="text-small text-gray-400">
                {{ $transaction->date->translatedFormat('d.m') }}
            </p>
        </div>
    </div>

    {{-- Swipe-to-delete action --}}
    <button
        x-show="showActions"
        x-on:click="if (confirm('{{ __('transactions.delete_confirm') }}')) $wire.deleteTransaction({{ $transaction->id }})"
        class="absolute inset-y-0 right-0 flex w-20 items-center justify-center bg-danger-500 text-white"
        x-transition
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
    </button>
</div>
