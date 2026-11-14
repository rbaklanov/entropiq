<?php

namespace App\Livewire\Auth;

use App\Actions\SendVerificationCode;
use App\Support\PhoneNumbers;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class LoginPage extends Component
{
    public string $country = PhoneNumbers::DEFAULT_REGION;

    public string $phone = '';

    public bool $consent = false;

    public bool $isSubmitting = false;

    public function sendCode(SendVerificationCode $action): void
    {
        $this->isSubmitting = true;

        $this->validate();

        $phone = $this->normalizedPhone();

        $action->execute($phone, request()->ip());

        session()->put('phone', $phone);
        session()->put('consent', [
            'accepted_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        session()->flash('code_sent', true);

        $this->redirectRoute('auth.verify');
    }

    /** @throws ValidationException */
    private function normalizedPhone(): string
    {
        if (! PhoneNumbers::isAllowedRegion($this->country)) {
            $this->isSubmitting = false;

            throw ValidationException::withMessages(['country' => __('auth.country_invalid')]);
        }

        $phone = PhoneNumbers::normalize($this->phone, $this->country);

        if ($phone === null) {
            $this->isSubmitting = false;

            throw ValidationException::withMessages(['phone' => __('auth.phone_invalid')]);
        }

        return $phone;
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:30'],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'consent.accepted' => __('auth.consent_required'),
            'country.required' => __('auth.country_invalid'),
            'country.size' => __('auth.country_invalid'),
        ];
    }

    public function render(): View
    {
        return view('livewire.auth.login-page', [
            'countries' => PhoneNumbers::countries(),
        ]);
    }
}
