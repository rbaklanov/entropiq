<?php

namespace App\Livewire\Auth;

use App\Actions\SendVerificationCode;
use App\Actions\VerifyCode;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class VerifyPage extends Component
{
    public string $phone = '';

    #[Validate('required|string|size:4')]
    public string $code = '';

    public bool $isSubmitting = false;

    public int $resendCooldown = 0;

    public function mount(): void
    {
        $this->phone = session('phone', '');

        if (! $this->phone) {
            $this->redirectRoute('auth.login');
        }

        $this->resendCooldown = VerificationCode::RESEND_COOLDOWN_SECONDS;
    }

    public function verify(VerifyCode $action): void
    {
        $this->isSubmitting = true;

        $this->validate();

        $user = $action->execute($this->phone, $this->code);

        Auth::login($user);

        session()->regenerate();
        session()->forget('phone');

        $this->redirect(route('dashboard'));
    }

    public function resendCode(SendVerificationCode $action): void
    {
        $action->execute($this->phone);

        $this->resendCooldown = VerificationCode::RESEND_COOLDOWN_SECONDS;

        $this->dispatch('timer-reset');

        session()->flash('success', __('auth.code_sent', ['phone' => $this->phone]));
    }

    public function render(): View
    {
        return view('livewire.auth.verify-page');
    }
}
