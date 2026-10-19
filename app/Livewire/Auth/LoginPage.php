<?php

namespace App\Livewire\Auth;

use App\Actions\SendVerificationCode;
use App\Support\PhoneFormatter;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class LoginPage extends Component
{
    #[Validate('required|string|regex:/^7\d{10}$/')]
    public string $phone = '';

    #[Validate('accepted')]
    public bool $consent = false;

    public bool $isSubmitting = false;

    public function sendCode(SendVerificationCode $action): void
    {
        $this->isSubmitting = true;

        $this->validate();

        $action->execute($this->phone);

        session()->put('phone', $this->phone);
        session()->put('consent', [
            'accepted_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        session()->flash('success', __('auth.code_sent', ['phone' => PhoneFormatter::mask($this->phone)]));

        $this->redirectRoute('auth.verify');
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'consent.accepted' => __('auth.consent_required'),
        ];
    }

    public function render(): View
    {
        return view('livewire.auth.login-page');
    }
}
