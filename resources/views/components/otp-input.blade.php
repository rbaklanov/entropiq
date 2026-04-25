@props([
    'name' => 'code',
    'length' => 4,
    'error' => null,
    'disabled' => false,
])

<div x-data="{
    digits: ['', '', '', ''],
    syncAll() {
        for (let i = 0; i < {{ $length }}; i++) {
            this.$refs['digit' + i].value = this.digits[i];
        }
        this.$refs.codeHidden.value = this.digits.join('');
    },
    onInput(index, e) {
        let val = e.target.value.replace(/\D/g, '');

        if (val.length > 1) {
            let chars = val.split('');
            for (let i = 0; i < chars.length && (index + i) < {{ $length }}; i++) {
                this.digits[index + i] = chars[i];
            }
            let nextIdx = Math.min(index + chars.length, {{ $length }} - 1);
            this.syncAll();
            this.$nextTick(() => this.$refs['digit' + nextIdx].focus());
            return;
        }

        this.digits[index] = val;
        e.target.value = val;
        this.$refs.codeHidden.value = this.digits.join('');

        if (val && index < {{ $length }} - 1) {
            this.$nextTick(() => this.$refs['digit' + (index + 1)].focus());
        }
    },
    onKeydown(index, e) {
        if (e.key === 'Backspace' && !this.digits[index] && index > 0) {
            this.digits[index - 1] = '';
            this.$refs['digit' + (index - 1)].value = '';
            this.$refs.codeHidden.value = this.digits.join('');
            this.$nextTick(() => this.$refs['digit' + (index - 1)].focus());
        }
    },
    onPaste(e) {
        e.preventDefault();
        let pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        for (let i = 0; i < {{ $length }}; i++) {
            this.digits[i] = pasted[i] || '';
        }
        this.syncAll();
        let focusIdx = Math.min(pasted.length, {{ $length }} - 1);
        this.$nextTick(() => this.$refs['digit' + focusIdx].focus());
    }
}" class="w-full">
    <label class="mb-1 block text-sm font-medium text-gray-700">
        {{ __('auth.sms_code') }}
    </label>

    <div class="flex justify-center gap-3">
        @for($i = 0; $i < $length; $i++)
            <input
                type="text"
                inputmode="numeric"
                maxlength="{{ $i === 0 ? 4 : 1 }}"
                x-ref="digit{{ $i }}"
                @input="onInput({{ $i }}, $event)"
                @keydown="onKeydown({{ $i }}, $event)"
                @paste="onPaste($event)"
                @if($disabled) disabled @endif
                @if($i === 0) autofocus @endif
                @class([
                    'h-14 w-14 rounded-xl border-2 text-center text-xl font-semibold outline-none transition-all duration-200',
                    'border-gray-200 bg-gray-50 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md' => !$error,
                    'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20' => $error,
                    'opacity-50' => $disabled,
                ])
            />
        @endfor
    </div>

    <input type="hidden" name="{{ $name }}" x-ref="codeHidden" />

    @if($error)
        <p class="mt-2 text-center text-sm text-danger-500">{{ $error }}</p>
    @endif
</div>
