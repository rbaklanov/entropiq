<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionPlan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->subscription_plan === SubscriptionPlan::Free) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('subscription.premium_required'),
                    'upgrade_url' => route('settings.subscription'),
                ], 403);
            }

            return redirect()->route('settings.subscription')
                ->with('warning', __('subscription.premium_required'));
        }

        return $next($request);
    }
}
