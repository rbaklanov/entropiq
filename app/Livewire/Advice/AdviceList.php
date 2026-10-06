<?php

namespace App\Livewire\Advice;

use App\Contracts\SubscriptionServiceInterface;
use App\Models\AiAdvice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AdviceList extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $subscriptionService = app(SubscriptionServiceInterface::class);

        $advices = AiAdvice::where('user_id', $user->id)
            ->orderByDesc('generated_at')
            ->get();

        return view('livewire.advice.advice-list', [
            'advices' => $advices,
            'premium' => $subscriptionService->isPremium($user),
            'openAdviceId' => $subscriptionService->openAdviceId($user),
        ]);
    }
}
