<?php

namespace App\Livewire\Auth;

use App\Actions\SendVerificationCode;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class LoginPage extends Component
{
    #[Validate('required|string|regex:/^7\d{10}$/')]
    public string $phone = '';

    public bool $isSubmitting = false;

    public function sendCode(SendVerificationCode $action): void
    {
        $this->isSubmitting = true;

        $this->validate();

        $action->execute($this->phone);

        session()->put('phone', $this->phone);
        session()->flash('success', __('auth.code_sent', ['phone' => $this->phone]));

        $this->redirectRoute('auth.verify');
    }

    public function render(): View
    {
        return view('livewire.auth.login-page');
    }
}
