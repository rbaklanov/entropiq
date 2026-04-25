<?php

namespace App\Livewire\Settings;

use App\Contracts\ExportServiceInterface;
use App\Models\Currency;
use App\Models\NotificationSetting;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class SettingsPage extends Component
{
    public bool $emailWeekly = true;

    public bool $pushGoals = true;

    public bool $pushAiAdvice = true;

    public string $locale = 'ru';

    public string $currencyCode = 'RUB';

    public function mount(): void
    {
        $user = auth()->user();
        $settings = $user->notificationSetting ?? NotificationSetting::defaultValues();

        $this->emailWeekly = is_array($settings) ? $settings['email_weekly'] : $settings->email_weekly;
        $this->pushGoals = is_array($settings) ? $settings['push_goals'] : $settings->push_goals;
        $this->pushAiAdvice = is_array($settings) ? $settings['push_ai_advice'] : $settings->push_ai_advice;
        $this->locale = $user->locale->value;
        $this->currencyCode = $user->currency_code;
    }

    public function updatedEmailWeekly(): void
    {
        $this->saveNotificationSettings();
    }

    public function updatedPushGoals(): void
    {
        $this->saveNotificationSettings();
    }

    public function updatedPushAiAdvice(): void
    {
        $this->saveNotificationSettings();
    }

    public function updateLocale(): void
    {
        auth()->user()->update(['locale' => $this->locale]);

        session()->flash('success', __('profile.updated'));
        $this->redirectRoute('settings.index');
    }

    public function updateCurrency(): void
    {
        auth()->user()->update(['currency_code' => $this->currencyCode]);

        session()->flash('success', __('profile.updated'));
        $this->redirectRoute('settings.index');
    }

    public function exportCsv(): StreamedResponse
    {
        $exportService = app(ExportServiceInterface::class);

        return $exportService->transactionsToCsv(auth()->user());
    }

    public function deleteAccount(): void
    {
        $user = auth()->user();

        $user->tokens()->delete();

        $user->update([
            'phone' => "deleted_{$user->id}",
            'name' => null,
        ]);

        $user->delete();

        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('landing');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.settings.settings-page', [
            'user' => $user,
            'currencies' => Currency::orderBy('code')->get(),
            'currentPlan' => $user->subscription_plan,
        ]);
    }

    private function saveNotificationSettings(): void
    {
        $user = auth()->user();

        $user->notificationSetting()->updateOrCreate([], [
            'email_weekly' => $this->emailWeekly,
            'push_goals' => $this->pushGoals,
            'push_ai_advice' => $this->pushAiAdvice,
        ]);
    }
}
