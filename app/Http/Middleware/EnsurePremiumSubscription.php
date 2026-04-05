<?php

namespace App\Http\Middleware;

use App\Contracts\SubscriptionServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumSubscription
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->subscriptionService->isPremium($user)) {
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
