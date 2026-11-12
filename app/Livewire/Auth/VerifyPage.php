<?php

namespace App\Livewire\Auth;

use App\Actions\RecordConsent;
use App\Actions\SendVerificationCode;
use App\Actions\VerifyCode;
use App\Contracts\VerificationDeliveryCheckerInterface;
use App\Enums\ConsentType;
use App\Enums\VerificationChannel;
use App\Enums\VerificationDeliveryStatus;
use App\Models\VerificationCode;
use App\Support\PhoneFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Random\RandomException;

#[Layout('components.layouts.guest')]
class VerifyPage extends Component
{
    public const MAX_DELIVERY_CHECKS = 12;

    public string $phone = '';

    #[Validate('required|string|size:4')]
    public string $code = '';

    public bool $isSubmitting = false;

    public int $resendCooldown = 0;

    public bool $watchDelivery = false;

    public bool $codeUndelivered = false;

    public int $deliveryChecks = 0;

    public function mount(): void
    {
        $this->phone = session('phone', '');

        if (! $this->phone) {
            $this->redirectRoute('auth.login');
        }

        $this->resendCooldown = VerificationCode::RESEND_COOLDOWN_SECONDS;

        $this->startWatchingDelivery();
    }

    public function checkDelivery(VerificationDeliveryCheckerInterface $checker): void
    {
        if (! $this->watchDelivery) {
            return;
        }

        $this->deliveryChecks++;

        $status = $checker->deliveryStatus($this->phone);

        $this->codeUndelivered = $status === VerificationDeliveryStatus::Undelivered;

        if ($status->isFinal() || $this->deliveryChecks >= self::MAX_DELIVERY_CHECKS) {
            $this->watchDelivery = false;
        }
    }

    /**
     * @throws ValidationException
     */
    public function verify(VerifyCode $action, RecordConsent $recordConsent): void
    {
        $this->isSubmitting = true;

        $this->validate();

        $user = $action->execute($this->phone, $this->code);

        $consent = session('consent');

        if (is_array($consent)) {
            $recordConsent->execute(
                $user,
                ConsentType::PersonalData,
                $consent['ip_address'] ?? null,
                $consent['user_agent'] ?? null,
                Carbon::parse($consent['accepted_at']),
            );
        }

        Auth::login($user);

        session()->regenerate();
        session()->forget(['phone', 'consent']);

        $destination = $user->hasCompletedOnboarding()
            ? route('dashboard')
            : route('onboarding.step', 1);

        $this->redirect($destination);
    }

    /**
     * @throws RandomException
     * @throws ValidationException
     */
    public function resendCode(SendVerificationCode $action): void
    {
        $action->execute($this->phone);

        $this->resendCooldown = VerificationCode::RESEND_COOLDOWN_SECONDS;

        $this->startWatchingDelivery();

        $this->dispatch('timer-reset');

        session()->flash('code_sent', true);
    }

    public function render(): View
    {
        return view('livewire.auth.verify-page', [
            'maskedPhone' => PhoneFormatter::mask($this->phone),
        ]);
    }

    private function startWatchingDelivery(): void
    {
        $this->watchDelivery = VerificationChannel::current() === VerificationChannel::Telegram;
        $this->codeUndelivered = false;
        $this->deliveryChecks = 0;
    }
}
