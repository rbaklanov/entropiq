<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            app()->setLocale($request->user()->locale->value);
        } elseif ($locale = session('locale')) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
