@props([
    'name' => 'code',
    'length' => 4,
    'error' => null,
    'disabled' => false,
])

<div x-data="{
    digits: Array({{ $length }}).fill(''),
    get code() {
        return this.digits.join('');
    },
    focusNext(index) {
        if (index < {{ $length }} - 1) {
            this.$refs['digit' + (index + 1)].focus();
        }
    },
    focusPrev(index) {
        if (index > 0) {
            this.$refs['digit' + (index - 1)].focus();
        }
    },
    onInput(index, e) {
        let val = e.target.value.replace(/\D/g, '');

        if (val.length > 1) {
            let chars = val.split('');
            for (let i = 0; i < chars.length && (index + i) < {{ $length }}; i++) {
                this.digits[index + i] = chars[i];
            }
            let nextIdx = Math.min(index + chars.length, {{ $length }} - 1);
            this.$refs['digit' + nextIdx].focus();
            return;
        }

        this.digits[index] = val;
        if (val) this.focusNext(index);
    },
    onKeydown(index, e) {
        if (e.key === 'Backspace' && !this.digits[index]) {
            this.focusPrev(index);
        }
    },
    onPaste(e) {
        e.preventDefault();
        let pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        for (let i = 0; i < {{ $length }}; i++) {
            this.digits[i] = pasted[i] || '';
        }
        let focusIdx = Math.min(pasted.length, {{ $length }} - 1);
        this.$refs['digit' + focusIdx].focus();
    }
}" class="w-full">
    <label class="mb-1 block text-sm font-medium text-gray-700">
        {{ __('Код из SMS') }}
    </label>

    <div class="flex justify-center gap-3">
        @for($i = 0; $i < $length; $i++)
            <input
                type="text"
                inputmode="numeric"
                maxlength="1"
                x-ref="digit{{ $i }}"
                :value="digits[{{ $i }}]"
                @input="onInput({{ $i }}, $event)"
                @keydown="onKeydown({{ $i }}, $event)"
                @paste="onPaste($event)"
                @if($disabled) disabled @endif
                @if($i === 0) autofocus @endif
                @class([
                    'h-14 w-14 rounded-lg border text-center text-xl font-semibold transition',
                    'border-gray-300 focus:border-primary-500 focus:ring-1 focus:ring-primary-500' => !$error,
                    'border-danger-500 focus:border-danger-500 focus:ring-1 focus:ring-danger-500' => $error,
                    'opacity-50' => $disabled,
                ])
            />
        @endfor
    </div>

    <input type="hidden" name="{{ $name }}" :value="code" />

    @if($error)
        <p class="mt-2 text-center text-sm text-danger-500">{{ $error }}</p>
    @endif
</div>
