<div class="mx-auto max-w-md px-4 py-20">
    <div class="rounded-md border border-gray-200 bg-white p-8">
        <h1 class="text-center text-h1">{{ __('auth.login_title') }}</h1>
        <p class="mt-2 text-center text-caption text-gray-500">
            {{ __('auth.login_subtitle') }}
        </p>
        @if(\App\Enums\VerificationChannel::current() === \App\Enums\VerificationChannel::Telegram)
            <p class="mt-2 text-center text-caption text-gray-500">{{ __('auth.login_hint_telegram') }}</p>
        @endif

        <form wire:submit="sendCode" class="mt-8">
            <div x-data="{
                countries: @js(collect($countries)->keyBy('code')->map(fn ($c) => ['dial' => $c['dial'], 'flag' => $c['flag'], 'example' => $c['example']])),
                country: $wire.entangle('country'),
                raw: '',
                get isRussia() {
                    return this.country === '{{ \App\Support\PhoneNumbers::DEFAULT_REGION }}' && !this.raw.startsWith('+');
                },
                get selected() {
                    return this.countries[this.country] ?? { dial: '', flag: '', example: '' };
                },
                get formatted() {
                    let d = this.raw;
                    if (!this.isRussia || !d) return d;
                    let result = '(' + d.substring(0, 3);
                    if (d.length > 3) result += ') ' + d.substring(3, 6);
                    if (d.length > 6) result += '-' + d.substring(6, 8);
                    if (d.length > 8) result += '-' + d.substring(8, 10);
                    return result;
                },
                get digitCount() {
                    return this.raw.replace(/\D/g, '').length;
                },
                get isComplete() {
                    return this.isRussia ? this.digitCount === 10 : this.digitCount >= 6;
                },
                onInput(e) {
                    let value = e.target.value;
                    let plus = value.trim().startsWith('+');
                    let digits = value.replace(/\D/g, '');
                    if (plus) {
                        this.raw = '+' + digits.substring(0, 15);
                    } else if (this.isRussia) {
                        this.raw = digits.substring(0, 10);
                    } else {
                        this.raw = value.replace(/[^\d\s\-()]/g, '').substring(0, 24);
                    }
                    e.target.value = this.formatted;
                    $wire.phone = this.raw;
                },
                onCountryChange() {
                    this.raw = '';
                    this.$refs.phone.value = '';
                    $wire.phone = '';
                    this.$refs.phone.focus();
                }
            }" class="w-full">
                <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">
                    {{ __('auth.phone_label') }}
                </label>

                <div @class([
                    'flex items-center overflow-hidden rounded-lg border transition',
                    'border-gray-300 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500' => !$errors->has('phone') && !$errors->has('country'),
                    'border-danger-500 focus-within:border-danger-500 focus-within:ring-1 focus-within:ring-danger-500' => $errors->has('phone') || $errors->has('country'),
                ])>
                    <div class="relative flex h-12 shrink-0 items-center gap-1 bg-gray-100 pl-3 pr-2 text-sm font-medium text-gray-700 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-600">
                        <span aria-hidden="true" x-text="selected.flag + ' +' + selected.dial">🇷🇺 +7</span>
                        <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                        <select
                            id="country"
                            x-model="country"
                            x-on:change="onCountryChange()"
                            aria-label="{{ __('auth.country_label') }}"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        >
                            @foreach($countries as $item)
                                <option value="{{ $item['code'] }}">{{ $item['flag'] }} {{ $item['name'] }} +{{ $item['dial'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <input
                        type="tel"
                        id="phone"
                        x-ref="phone"
                        @input="onInput($event)"
                        :placeholder="isRussia ? '(900) 123-45-67' : selected.example"
                        placeholder="(900) 123-45-67"
                        autocomplete="tel-national"
                        inputmode="tel"
                        autofocus
                        class="h-12 w-full min-w-0 border-0 bg-white px-3 text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
                    />
                </div>

                @error('country')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
                @error('phone')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror

                <label for="consent" class="mt-5 flex items-start gap-3 text-sm text-gray-900">
                    <input
                        type="checkbox"
                        id="consent"
                        wire:model="consent"
                        class="mt-0.5 h-5 w-5 shrink-0 rounded border-gray-300 accent-primary-600"
                    />
                    <span>
                        {{ __('auth.consent_label') }}
                        <a href="{{ route('consent') }}" class="underline" target="_blank">{{ __('auth.consent_link') }}</a>
                    </span>
                </label>

                @error('consent')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    x-bind:disabled="!isComplete || $wire.isSubmitting"
                    class="mt-6 w-full min-h-12 rounded-md bg-primary-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-primary-700 disabled:opacity-50"
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

        <p class="mt-4 text-center text-small text-gray-500">
            {{ __('auth.terms_agreement') }}
            <a href="{{ route('terms') }}" class="underline" target="_blank">{{ __('auth.terms_link') }}</a>
            {{ __('auth.and') }}
            <a href="{{ route('privacy') }}" class="underline" target="_blank">{{ __('auth.privacy_link') }}</a>
        </p>
    </div>
</div>
