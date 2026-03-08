<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->phone_verified_at) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.phone_not_verified')], 403);
            }

            return redirect()->route('auth.login');
        }

        return $next($request);
    }
}
