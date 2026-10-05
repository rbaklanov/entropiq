<?php

namespace App\Livewire\Settings;

use App\Actions\ChangeUserEmail;
use App\Actions\SendEmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProfilePage extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name ?? '';
        $this->email = auth()->user()->email ?? '';
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore(auth()->id())->whereNull('deleted_at'),
            ],
        ];
    }

    public function save(ChangeUserEmail $changeUserEmail): void
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate();

        $user = auth()->user();

        $user->update(['name' => $this->name]);
        $changeUserEmail->execute($user, $this->email);

        $message = $user->wasChanged('email') && filled($user->email)
            ? __('profile.email_confirmation_sent', ['email' => $user->email])
            : __('profile.updated');

        session()->flash('success', $message);
        $this->redirectRoute('settings.profile');
    }

    public function resendVerification(SendEmailVerification $sendEmailVerification): void
    {
        $user = auth()->user();

        if (blank($user->email) || $user->hasVerifiedEmail()) {
            return;
        }

        $allowed = RateLimiter::attempt(
            "email-verification:{$user->id}",
            1,
            fn () => $sendEmailVerification->execute($user),
            60,
        );

        session()->flash(
            'success',
            $allowed ? __('profile.email_confirmation_sent', ['email' => $user->email]) : __('profile.email_confirmation_throttled'),
        );
    }

    public function render(): View
    {
        return view('livewire.settings.profile-page', [
            'user' => auth()->user(),
        ]);
    }
}
