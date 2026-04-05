<?php

namespace App\Livewire\Advice;

use App\Contracts\SubscriptionServiceInterface;
use App\Models\AiAdvice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AdviceDetail extends Component
{
    public AiAdvice $advice;

    public bool $locked = false;

    public function mount(AiAdvice $advice): void
    {
        abort_unless($advice->user_id === auth()->id(), 403);

        $this->advice = $advice;

        $subscriptionService = app(SubscriptionServiceInterface::class);

        if (! $subscriptionService->canViewAdvice(auth()->user(), $advice)) {
            $this->locked = true;

            return;
        }

        if (! $advice->is_read) {
            $advice->markAsRead();
        }
    }

    public function rate(int $rating): void
    {
        $this->advice->update(['rating' => $rating]);
    }

    public function render(): View
    {
        return view('livewire.advice.advice-detail');
    }
}
