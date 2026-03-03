@props([
    'name' => 'phone',
    'value' => '',
    'error' => null,
    'disabled' => false,
])

<div x-data="{
    raw: '{{ old($name, $value) }}'.replace(/\D/g, '').replace(/^7/, ''),
    get formatted() {
        let d = this.raw;
        if (!d) return '';
        let result = '(' + d.substring(0, 3);
        if (d.length > 3) result += ') ' + d.substring(3, 6);
        if (d.length > 6) result += '-' + d.substring(6, 8);
        if (d.length > 8) result += '-' + d.substring(8, 10);
        return result;
    },
    onInput(e) {
        let digits = e.target.value.replace(/\D/g, '');
        this.raw = digits.substring(0, 10);
    },
    get fullNumber() {
        return this.raw.length === 10 ? '7' + this.raw : '';
    }
}" class="w-full">
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-gray-700">
        {{ __('Номер телефона') }}
    </label>

    <div @class([
        'flex items-center overflow-hidden rounded-lg border transition',
        'border-gray-300 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500' => !$error,
        'border-danger-500 focus-within:border-danger-500 focus-within:ring-1 focus-within:ring-danger-500' => $error,
        'opacity-50' => $disabled,
    ])>
        <span class="flex h-12 items-center bg-gray-50 px-3 text-sm font-medium text-gray-500">+7</span>

        <input
            type="tel"
            id="{{ $name }}"
            :value="formatted"
            @input="onInput($event)"
            @if($disabled) disabled @endif
            placeholder="(900) 123-45-67"
            autocomplete="tel"
            inputmode="numeric"
            class="h-12 w-full border-0 bg-white px-3 text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
        />
    </div>

    <input type="hidden" name="{{ $name }}" :value="fullNumber" />

    @if($error)
        <p class="mt-1 text-sm text-danger-500">{{ $error }}</p>
    @endif
</div>
