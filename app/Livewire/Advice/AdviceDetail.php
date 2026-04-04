<?php

namespace App\Livewire\Advice;

use App\Models\AiAdvice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AdviceDetail extends Component
{
    public AiAdvice $advice;

    public function mount(AiAdvice $advice): void
    {
        abort_unless($advice->user_id === auth()->id(), 403);

        $this->advice = $advice;

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
