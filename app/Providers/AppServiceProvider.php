<?php

namespace App\Providers;

use App\Contracts\AiAdviceServiceInterface;
use App\Contracts\AnalyticsServiceInterface;
use App\Contracts\ExportServiceInterface;
use App\Contracts\GoalCalculationServiceInterface;
use App\Contracts\InflationServiceInterface;
use App\Contracts\LlmServiceInterface;
use App\Contracts\PaymentServiceInterface;
use App\Contracts\SmsServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Integrations\SmsAero\SmsAeroConnector;
use App\Models\User;
use App\Services\AiAdviceService;
use App\Services\AnalyticsService;
use App\Services\ExportService;
use App\Services\FakeLlmService;
use App\Services\FakePaymentService;
use App\Services\GigaChatService;
use App\Services\GoalCalculationService;
use App\Services\InflationService;
use App\Services\LogSmsService;
use App\Services\SmsAeroService;
use App\Services\SubscriptionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        InflationServiceInterface::class => InflationService::class,
        GoalCalculationServiceInterface::class => GoalCalculationService::class,
        AiAdviceServiceInterface::class => AiAdviceService::class,
        PaymentServiceInterface::class => FakePaymentService::class,
        SubscriptionServiceInterface::class => SubscriptionService::class,
        ExportServiceInterface::class => ExportService::class,
        AnalyticsServiceInterface::class => AnalyticsService::class,
    ];

    public function register(): void
    {
        $this->app->bind(GigaChatService::class, function (Container $app): GigaChatService {
            $config = config('services.gigachat', []);
            $clientId = is_array($config) && isset($config['client_id']) ? (string) $config['client_id'] : '';
            $clientSecret = is_array($config) && isset($config['client_secret']) ? (string) $config['client_secret'] : '';
            $scope = is_array($config) && isset($config['scope']) ? (string) $config['scope'] : 'GIGACHAT_API_PERS';
            $model = is_array($config) && isset($config['model']) ? (string) $config['model'] : 'GigaChat';
            $verifySsl = is_array($config) && isset($config['verify_ssl']) ? (bool) $config['verify_ssl'] : false;
            $timeout = is_array($config) && isset($config['timeout']) ? (float) $config['timeout'] : 30.0;

            return new GigaChatService(
                fallbackService: $app->make(FakeLlmService::class),
                clientId: $clientId,
                clientSecret: $clientSecret,
                scope: $scope,
                model: $model,
                verifySsl: $verifySsl,
                timeout: $timeout,
            );
        });

        $this->app->bind(LlmServiceInterface::class, function (Container $app): LlmServiceInterface {
            $driver = config('services.llm.driver', 'fake');

            return match ($driver) {
                'gigachat' => $app->make(GigaChatService::class),
                default => $app->make(FakeLlmService::class),
            };
        });

        $this->app->bind(SmsAeroConnector::class, function (): SmsAeroConnector {
            $email = config('services.sms_aero.email');
            $apiKey = config('services.sms_aero.api_key');

            return new SmsAeroConnector(
                email: is_string($email) ? $email : '',
                apiKey: is_string($apiKey) ? $apiKey : '',
            );
        });

        $this->app->bind(SmsAeroService::class, function (Container $app): SmsAeroService {
            $sign = config('services.sms_aero.sign');

            return new SmsAeroService(
                connector: $app->make(SmsAeroConnector::class),
                sign: is_string($sign) && $sign !== '' ? $sign : 'Entropiq',
                testMode: config('services.sms_aero.test_mode') === true,
            );
        });

        $this->app->bind(SmsServiceInterface::class, function (Container $app): SmsServiceInterface {
            $driver = config('services.sms.driver');

            if ($driver === 'sms_aero') {
                return $app->make(SmsAeroService::class);
            }

            return $app->make(LogSmsService::class);
        });

        if ($this->app->environment('local') && class_exists(TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        RateLimiter::for('sms', fn () => Limit::perMinute(1)->by(request()->ip()));

        RateLimiter::for('verify', fn () => Limit::perMinute(5)->by(request()->ip()));

        Gate::define('viewPulse', fn (?User $user): bool => $user?->isAdmin() === true);

        Pulse::user(fn (User $user): array => [
            'name' => $user->name ?: $user->phone,
            'extra' => $user->phone,
        ]);
    }
}
