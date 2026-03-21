<div class="mx-auto max-w-md px-4 py-20">
    <div class="rounded-2xl bg-white p-8 shadow-sm">
        <h1 class="text-center text-h2">{{ __('auth.verify_title') }}</h1>
        <p class="mt-2 text-center text-caption text-gray-500">
            {{ __('auth.verify_subtitle', ['phone' => $phone]) }}
        </p>

        @if(session('success'))
            <div class="mt-4 rounded-lg bg-success-50 p-3 text-center text-sm text-success-700">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="verify" class="mt-8">
            <div x-data="{
                digits: ['', '', '', ''],
                syncAll() {
                    for (let i = 0; i < 4; i++) {
                        this.$refs['digit' + i].value = this.digits[i];
                    }
                    $wire.set('code', this.digits.join(''));
                },
                onInput(index, e) {
                    let val = e.target.value.replace(/\D/g, '');

                    if (val.length > 1) {
                        let chars = val.split('');
                        for (let i = 0; i < chars.length && (index + i) < 4; i++) {
                            this.digits[index + i] = chars[i];
                        }
                        let nextIdx = Math.min(index + chars.length, 3);
                        this.syncAll();
                        this.$nextTick(() => this.$refs['digit' + nextIdx].focus());
                        return;
                    }

                    this.digits[index] = val;
                    e.target.value = val;
                    $wire.set('code', this.digits.join(''));

                    if (val && index < 3) {
                        this.$nextTick(() => this.$refs['digit' + (index + 1)].focus());
                    }
                },
                onKeydown(index, e) {
                    if (e.key === 'Backspace' && !this.digits[index] && index > 0) {
                        this.digits[index - 1] = '';
                        this.$refs['digit' + (index - 1)].value = '';
                        $wire.set('code', this.digits.join(''));
                        this.$nextTick(() => this.$refs['digit' + (index - 1)].focus());
                    }
                },
                onPaste(e) {
                    e.preventDefault();
                    let pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                    for (let i = 0; i < 4; i++) {
                        this.digits[i] = pasted[i] || '';
                    }
                    this.syncAll();
                    let focusIdx = Math.min(pasted.length, 3);
                    this.$nextTick(() => this.$refs['digit' + focusIdx].focus());
                }
            }" class="w-full">
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    {{ __('Код из SMS') }}
                </label>

                <div class="flex justify-center gap-3">
                    @for($i = 0; $i < 4; $i++)
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="{{ $i === 0 ? 4 : 1 }}"
                            x-ref="digit{{ $i }}"
                            @input="onInput({{ $i }}, $event)"
                            @keydown="onKeydown({{ $i }}, $event)"
                            @paste="onPaste($event)"
                            @if($i === 0) autofocus @endif
                            @class([
                                'h-14 w-14 rounded-xl border-2 text-center text-xl font-semibold outline-none transition-all duration-200',
                                'border-gray-200 bg-gray-50 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md' => !$errors->has('code'),
                                'border-danger-400 bg-danger-50 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20' => $errors->has('code'),
                            ])
                        />
                    @endfor
                </div>

                @error('code')
                    <p class="mt-2 text-center text-sm text-danger-500">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-primary-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="verify">{{ __('auth.verify_button') }}</span>
                <span wire:loading wire:target="verify" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('auth.verify_button') }}
                </span>
            </button>
        </form>

        <div
            x-data="{
                seconds: @js($resendCooldown),
                interval: null,
                start() {
                    this.interval = setInterval(() => {
                        if (this.seconds > 0) this.seconds--;
                        if (this.seconds === 0) clearInterval(this.interval);
                    }, 1000);
                },
                init() {
                    this.start();
                }
            }"
            x-on:timer-reset.window="seconds = @js(\App\Models\VerificationCode::RESEND_COOLDOWN_SECONDS); clearInterval(interval); start()"
            class="mt-4 text-center"
        >
            <template x-if="seconds > 0">
                <p class="text-caption text-gray-400">
                    {{ __('Повторная отправка через') }} <span x-text="seconds"></span> {{ __('сек') }}
                </p>
            </template>

            <template x-if="seconds === 0">
                <button
                    type="button"
                    wire:click="resendCode"
                    class="text-caption text-primary-600 transition hover:underline"
                >
                    {{ __('auth.resend_code') }}
                </button>
            </template>
        </div>

        <p class="mt-4 text-center text-caption text-gray-500">
            <a href="{{ route('auth.login') }}" class="text-primary-600 hover:underline">
                {{ __('auth.change_number') }}
            </a>
        </p>
    </div>
</div>
