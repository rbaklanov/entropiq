<?php

namespace App\Livewire\Settings;

use App\Contracts\PaymentServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SubscriptionPage extends Component
{
    public string $selectedPlan = 'monthly';

    public function selectPlan(string $plan): void
    {
        $this->selectedPlan = $plan;
    }

    public function subscribe(): void
    {
        $plan = $this->selectedPlan === 'yearly'
            ? SubscriptionPlan::Yearly
            : SubscriptionPlan::Monthly;

        $subscriptionService = app(SubscriptionServiceInterface::class);
        $paymentService = app(PaymentServiceInterface::class);

        $user = auth()->user();

        $paymentService->charge($user, $plan);

        $subscriptionService->subscribe($user, $plan);
        $user->refresh();

        session()->flash('success', __('subscription.upgraded'));
        $this->redirectRoute('settings.subscription');
    }

    public function cancel(): void
    {
        $user = auth()->user();
        $subscriptionService = app(SubscriptionServiceInterface::class);

        $subscriptionService->cancel($user);
        $user->refresh();

        session()->flash('success', __('subscription.cancelled'));
        $this->redirectRoute('settings.subscription');
    }

    public function render(): View
    {
        $user = auth()->user();
        $subscriptionService = app(SubscriptionServiceInterface::class);

        $activeSubscription = Subscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->first();

        return view('livewire.settings.subscription-page', [
            'isPremium' => $subscriptionService->isPremium($user),
            'currentPlan' => $user->subscription_plan,
            'activeSubscription' => $activeSubscription,
        ]);
    }
}
