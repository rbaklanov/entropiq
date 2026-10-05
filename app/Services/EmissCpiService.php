<?php

namespace App\Services;

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Integrations\Emiss\EmissConnector;
use App\Integrations\Emiss\EmissCpiCatalog as Catalog;
use App\Integrations\Emiss\Requests\GetCpiGridRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmissCpiService implements CpiProviderInterface
{
    private const COLUMN_PATTERN = '/^dim(\d{4})_(\d+)_(\d+)_/';

    public function __construct(
        private readonly EmissConnector $connector,
    ) {}

    /** {@inheritDoc} */
    public function fetch(Carbon $from, Carbon $to): Collection
    {
        $records = collect();

        foreach (range($from->year, $to->year) as $year) {
            $records = $records->concat($this->fetchYear($year));
        }

        $fromMonth = $from->copy()->startOfMonth();
        $toMonth = $to->copy()->startOfMonth();

        return $records
            ->filter(fn (CpiRecord $record) => $record->period->between($fromMonth, $toMonth))
            ->values();
    }

    /** @return Collection<int, CpiRecord> */
    private function fetchYear(int $year): Collection
    {
        $response = $this->connector
            ->send(new GetCpiGridRequest($year))
            ->throw();

        /** @var array{results?: array<int, array<string, string>>} $payload */
        $payload = $response->json();

        $records = collect();

        foreach ($payload['results'] ?? [] as $row) {
            $categoryCode = Catalog::categoryCodeByTitle((string) ($row['dim'.Catalog::FILTER_GOODS] ?? ''));

            if ($categoryCode === null) {
                continue;
            }

            foreach ($row as $column => $rawValue) {
                $record = $this->parseColumn($column, (string) $rawValue, $categoryCode);

                if ($record !== null) {
                    $records->push($record);
                }
            }
        }

        return $records;
    }

    private function parseColumn(string $column, string $rawValue, string $categoryCode): ?CpiRecord
    {
        if (! preg_match(self::COLUMN_PATTERN, $column, $matches)) {
            return null;
        }

        [, $year, $periodId, $kindId] = $matches;

        if ((int) $kindId !== Catalog::KIND_PREVIOUS_MONTH) {
            return null;
        }

        $month = Catalog::monthByPeriodId((int) $periodId);
        $value = str_replace(',', '.', trim($rawValue));

        if ($month === null || ! is_numeric($value)) {
            return null;
        }

        return new CpiRecord(
            period: Carbon::create((int) $year, $month, 1)->startOfDay(),
            categoryCode: $categoryCode,
            value: (float) $value,
        );
    }
}
