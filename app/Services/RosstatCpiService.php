<?php

namespace App\Services;

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Integrations\Emiss\EmissCpiCatalog;
use App\Integrations\Rosstat\Requests\DownloadWorkbookRequest;
use App\Integrations\Rosstat\Requests\GetPricesPageRequest;
use App\Integrations\Rosstat\RosstatConnector;
use App\Models\CpiValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class RosstatCpiService implements CpiProviderInterface
{
    private const WORKBOOK_LINK_PATTERN = '#/storage/mediabank/ipc_spr_(\d{2})-(\d{4})\.xlsx#';

    private const SHEET_NAME_PATTERN = '/^(\d{2})\d*(\d{4})$/';

    private const SHEET_TITLE_PATTERN = '/за\s+(\p{L}+)\s+(\d{4})/u';

    private const FIRST_DATA_ROW = 7;

    private const NAME_COLUMN = 1;

    private const PREVIOUS_MONTH_COLUMN = 4;

    private const PREVIOUS_MONTH_HEADER = 'к предыдущему месяцу';

    /** @var array<string, int> */
    private const MONTHS = [
        'январь' => 1, 'февраль' => 2, 'март' => 3, 'апрель' => 4, 'май' => 5, 'июнь' => 6,
        'июль' => 7, 'август' => 8, 'сентябрь' => 9, 'октябрь' => 10, 'ноябрь' => 11, 'декабрь' => 12,
    ];

    public function __construct(
        private readonly RosstatConnector $connector,
    ) {}

    /** {@inheritDoc} */
    public function fetch(Carbon $from, Carbon $to): Collection
    {
        $path = $this->latestWorkbookPath();

        $file = tempnam(sys_get_temp_dir(), 'rosstat-cpi-');

        try {
            file_put_contents($file, $this->connector->send(new DownloadWorkbookRequest($path))->throw()->body());

            return $this->readWorkbook($file, $from->copy()->startOfMonth(), $to->copy()->startOfMonth());
        } finally {
            @unlink($file);
        }
    }

    private function latestWorkbookPath(): string
    {
        $page = $this->connector->send(new GetPricesPageRequest)->throw()->body();

        preg_match_all(self::WORKBOOK_LINK_PATTERN, $page, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            throw new RuntimeException('Rosstat prices page does not link a monthly CPI workbook (ipc_spr).');
        }

        usort($matches, fn (array $a, array $b) => [$b[2], $b[1]] <=> [$a[2], $a[1]]);

        return $matches[0][0];
    }

    /** @return Collection<int, CpiRecord> */
    private function readWorkbook(string $file, Carbon $from, Carbon $to): Collection
    {
        $reader = new Xlsx;
        $reader->setReadDataOnly(true);

        $sheetNames = collect($reader->listWorksheetNames($file))
            ->filter(function (string $name) use ($from, $to) {
                $month = $this->monthFromSheetName($name);

                return $month !== null && $month->between($from, $to);
            })
            ->values()
            ->all();

        if ($sheetNames === []) {
            return collect();
        }

        $records = collect();

        foreach ($sheetNames as $sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
            $spreadsheet = $reader->load($file);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $month = $this->monthFromSheetTitle($sheet);

                if ($month === null || $month->toDateString() !== $this->monthFromSheetName($sheet->getTitle())?->toDateString()) {
                    Log::warning('Rosstat CPI sheet skipped, its title does not match its name', ['sheet' => $sheet->getTitle()]);

                    continue;
                }

                $records = $records->concat($this->readSheet($sheet, $month));
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return $records;
    }

    /** @return Collection<int, CpiRecord> */
    private function readSheet(Worksheet $sheet, Carbon $month): Collection
    {
        if (! $this->hasPreviousMonthColumn($sheet)) {
            throw new RuntimeException("Rosstat CPI sheet {$sheet->getTitle()} has an unexpected layout.");
        }

        $rows = $sheet->rangeToArray(
            'A'.self::FIRST_DATA_ROW.':D'.$sheet->getHighestRow(),
            null,
            true,
            false,
        );

        $records = [];

        foreach ($rows as $row) {
            $title = trim((string) preg_replace('/\s+/u', ' ', (string) $row[self::NAME_COLUMN - 1]));
            $code = EmissCpiCatalog::categoryCodeByTitle($title);
            $value = $row[self::PREVIOUS_MONTH_COLUMN - 1];

            if ($code === null || isset($records[$code]) || ! is_numeric($value)) {
                continue;
            }

            $records[$code] = new CpiRecord($month->copy(), $code, (float) $value, CpiValue::SOURCE_ROSSTAT_WORKBOOK);
        }

        return collect(array_values($records));
    }

    private function hasPreviousMonthColumn(Worksheet $sheet): bool
    {
        foreach (range(4, self::FIRST_DATA_ROW - 1) as $row) {
            $header = (string) $sheet->getCell([self::PREVIOUS_MONTH_COLUMN, $row])->getValue();

            if (mb_stripos($header, self::PREVIOUS_MONTH_HEADER) !== false) {
                return true;
            }
        }

        return false;
    }

    private function monthFromSheetName(string $name): ?Carbon
    {
        if (! preg_match(self::SHEET_NAME_PATTERN, $name, $parts)) {
            return null;
        }

        $month = (int) $parts[1];
        $year = (int) $parts[2];

        return $month >= 1 && $month <= 12 && $year >= 2000
            ? Carbon::create($year, $month, 1)->startOfDay()
            : null;
    }

    private function monthFromSheetTitle(Worksheet $sheet): ?Carbon
    {
        $title = (string) $sheet->getCell([1, 3])->getValue();

        if (! preg_match(self::SHEET_TITLE_PATTERN, $title, $parts)) {
            return null;
        }

        $month = self::MONTHS[mb_strtolower($parts[1])] ?? null;

        return $month === null ? null : Carbon::create((int) $parts[2], $month, 1)->startOfDay();
    }
}
