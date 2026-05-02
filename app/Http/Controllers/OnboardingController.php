<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const TOTAL_STEPS = 3;

    public function step(Request $request, int $step): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        $step = max(1, min($step, self::TOTAL_STEPS));

        return view("pages.app.onboarding.step{$step}", [
            'step' => $step,
            'totalSteps' => self::TOTAL_STEPS,
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasCompletedOnboarding()) {
            $user->update(['onboarding_completed_at' => now()]);
        }

        return redirect()->route('dashboard');
    }

    public function skip(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasCompletedOnboarding()) {
            $user->update(['onboarding_completed_at' => now()]);
        }

        return redirect()->route('dashboard');
    }
}
