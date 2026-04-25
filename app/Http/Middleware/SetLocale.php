<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            app()->setLocale($user->locale->value);
        }

        return $next($request);
    }
}
