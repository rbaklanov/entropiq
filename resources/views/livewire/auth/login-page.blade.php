<div class="mx-auto max-w-md px-4 py-20">
    <div class="rounded-2xl bg-white p-8 shadow-sm">
        <h1 class="text-center text-h2">{{ __('auth.login_title') }}</h1>
        <p class="mt-2 text-center text-caption text-gray-500">
            {{ __('auth.login_subtitle') }}
        </p>

        <form wire:submit="sendCode" class="mt-8">
            <div x-data="{
                raw: '',
                get formatted() {
                    let d = this.raw;
                    if (!d) return '';
                    let result = '(' + d.substring(0, 3);
                    if (d.length > 3) result += ') ' + d.substring(3, 6);
                    if (d.length > 6) result += '-' + d.substring(6, 8);
                    if (d.length > 8) result += '-' + d.substring(8, 10);
                    return result;
                },
                get isComplete() {
                    return this.raw.length === 10;
                },
                onInput(e) {
                    let digits = e.target.value.replace(/\D/g, '');
                    this.raw = digits.substring(0, 10);
                    $wire.set('phone', this.raw.length === 10 ? '7' + this.raw : '');
                }
            }" class="w-full">
                <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">
                    {{ __('auth.phone_label') }}
                </label>

                <div @class([
                    'flex items-center overflow-hidden rounded-lg border transition',
                    'border-gray-300 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500' => !$errors->has('phone'),
                    'border-danger-500 focus-within:border-danger-500 focus-within:ring-1 focus-within:ring-danger-500' => $errors->has('phone'),
                ])>
                    <span class="flex h-12 items-center bg-gray-50 px-3 text-sm font-medium text-gray-500">+7</span>

                    <input
                        type="tel"
                        id="phone"
                        :value="formatted"
                        @input="onInput($event)"
                        placeholder="(900) 123-45-67"
                        autocomplete="tel"
                        inputmode="numeric"
                        autofocus
                        class="h-12 w-full border-0 bg-white px-3 text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
                    />
                </div>

                @error('phone')
                    <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    x-bind:disabled="!isComplete || $wire.isSubmitting"
                    class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-primary-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="sendCode">{{ __('auth.send_code') }}</span>
                    <span wire:loading wire:target="sendCode" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ __('auth.send_code') }}
                    </span>
                </button>
            </div>
        </form>

        <p class="mt-4 text-center text-small text-gray-400">
            {{ __('auth.terms_agreement') }}
            <a href="#" class="underline">{{ __('auth.terms_link') }}</a>
        </p>
    </div>
</div>
