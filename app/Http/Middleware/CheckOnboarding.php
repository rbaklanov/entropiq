<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasCompletedOnboarding()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('onboarding.required')], 403);
            }

            return redirect()->route('onboarding.step', 1);
        }

        return $next($request);
    }
}
