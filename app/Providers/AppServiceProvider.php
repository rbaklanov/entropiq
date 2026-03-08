<?php

namespace App\Providers;

use App\Contracts\AiAdviceServiceInterface;
use App\Contracts\ExportServiceInterface;
use App\Contracts\GoalCalculationServiceInterface;
use App\Contracts\InflationServiceInterface;
use App\Contracts\SmsServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Services\AiAdviceService;
use App\Services\ExportService;
use App\Services\GoalCalculationService;
use App\Services\InflationService;
use App\Services\LogSmsService;
use App\Services\SubscriptionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        SmsServiceInterface::class => LogSmsService::class,
        InflationServiceInterface::class => InflationService::class,
        GoalCalculationServiceInterface::class => GoalCalculationService::class,
        AiAdviceServiceInterface::class => AiAdviceService::class,
        SubscriptionServiceInterface::class => SubscriptionService::class,
        ExportServiceInterface::class => ExportService::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('sms', fn () => Limit::perMinute(1)->by(request()->ip()));

        RateLimiter::for('verify', fn () => Limit::perMinute(5)->by(request()->ip()));
    }
}
