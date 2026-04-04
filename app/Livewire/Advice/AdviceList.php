<?php

namespace App\Livewire\Advice;

use App\Models\AiAdvice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AdviceList extends Component
{
    public function render(): View
    {
        $advices = AiAdvice::where('user_id', auth()->id())
            ->orderByDesc('generated_at')
            ->get();

        return view('livewire.advice.advice-list', [
            'advices' => $advices,
        ]);
    }
}
