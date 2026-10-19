<?php

namespace App\Livewire\Settings;

use App\Actions\StartCheckout;
use App\Contracts\SubscriptionServiceInterface;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionRenewalService;
use App\Support\Money;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SubscriptionPage extends Component
{
    public string $selectedPlan = 'monthly';

    public bool $autoRenew = false;

    public function selectPlan(string $plan): void
    {
        $this->selectedPlan = $plan;
    }

    public function subscribe(StartCheckout $startCheckout): void
    {
        $plan = $this->selectedPlan === 'yearly'
            ? SubscriptionPlan::Yearly
            : SubscriptionPlan::Monthly;

        $url = $startCheckout->execute(
            auth()->user(),
            $plan,
            $this->autoRenew,
            request()->ip(),
            request()->userAgent(),
        );

        $this->redirect($url);
    }

    public function disableAutoRenew(SubscriptionServiceInterface $subscriptionService): void
    {
        $subscriptionService->disableAutoRenew(auth()->user());

        session()->flash('success', __('payments.auto_renew_disabled'));
        $this->redirectRoute('settings.subscription');
    }

    public function render(SubscriptionServiceInterface $subscriptionService): View
    {
        $user = auth()->user();

        $activeSubscription = Subscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->first();

        $renewsSoon = $activeSubscription?->auto_renew === true
            && $activeSubscription->ends_at !== null
            && $activeSubscription->ends_at->lte(now()->addDays(SubscriptionRenewalService::REMINDER_DAYS));

        return view('livewire.settings.subscription-page', [
            'isPremium' => $subscriptionService->isPremium($user),
            'currentPlan' => $user->subscription_plan,
            'activeSubscription' => $activeSubscription,
            'renewsSoon' => $renewsSoon,
            'renewalAmount' => $activeSubscription === null ? null : Money::amount($activeSubscription->plan->price()),
        ]);
    }
}
