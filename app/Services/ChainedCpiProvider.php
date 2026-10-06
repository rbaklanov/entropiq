<?php

namespace App\Services;

use App\Contracts\CpiProviderInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChainedCpiProvider implements CpiProviderInterface
{
    /** @param array<int, CpiProviderInterface> $providers */
    public function __construct(private readonly array $providers) {}

    /** {@inheritDoc} */
    public function fetch(Carbon $from, Carbon $to): Collection
    {
        $lastFailure = null;

        foreach ($this->providers as $provider) {
            try {
                $records = $provider->fetch($from, $to);
            } catch (Throwable $exception) {
                Log::warning('CPI source failed, trying the next one', [
                    'source' => $provider::class,
                    'message' => $exception->getMessage(),
                ]);
                $lastFailure = $exception;

                continue;
            }

            if ($records->isNotEmpty()) {
                return $records;
            }
        }

        if ($lastFailure !== null) {
            throw $lastFailure;
        }

        return collect();
    }
}
