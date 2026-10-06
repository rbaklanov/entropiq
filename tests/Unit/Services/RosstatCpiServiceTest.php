<?php

use App\Integrations\Rosstat\Requests\DownloadWorkbookRequest;
use App\Integrations\Rosstat\Requests\GetPricesPageRequest;
use App\Integrations\Rosstat\RosstatConnector;
use App\Models\CpiValue;
use App\Services\RosstatCpiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * @param  array<int, array{name: string, title: string, rows: array<string, mixed>, header?: string}>  $sheets
 */
function rosstatWorkbook(array $sheets): string
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->removeSheetByIndex(0);
    $spreadsheet->createSheet()->setTitle('Содержание');

    foreach ($sheets as $definition) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($definition['name']);
        $sheet->setCellValue('A1', 'К содержанию');
        $sheet->setCellValue('A3', $definition['title']);
        $sheet->setCellValue('D5', 'Индексы потребительских цен, в %');
        $sheet->setCellValue('D6', $definition['header'] ?? 'к предыдущему месяцу');

        $row = 7;

        foreach ($definition['rows'] as $title => $value) {
            $sheet->setCellValue("A{$row}", $title);
            $sheet->setCellValue("D{$row}", $value);
            $row++;
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'rosstat-test-');
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
    $bytes = file_get_contents($path);
    unlink($path);

    return $bytes;
}

function rosstatSheet(string $name, string $monthTitle, array $rows): array
{
    return [
        'name' => $name,
        'title' => "Индексы потребительских цен по Российской Федерации за {$monthTitle} г.1)",
        'rows' => $rows,
    ];
}

function rosstatPage(string ...$files): string
{
    $links = array_map(fn (string $file) => "<li><a href=\"/storage/mediabank/{$file}\">XLSX</a></li>", $files);

    return '<html><body><ul>'.implode('', $links).'<li><a href="/storage/mediabank/ipc_mes_08-2026.xlsx">XLSX</a></li></ul></body></html>';
}

beforeEach(function () {
    $this->connector = new RosstatConnector;
    $this->connector->tries = 1;
    $this->service = new RosstatCpiService($this->connector);

    $this->serve = function (string $page, ?string $workbook = null): MockClient {
        $mock = new MockClient([
            GetPricesPageRequest::class => MockResponse::make($page),
            DownloadWorkbookRequest::class => MockResponse::make($workbook ?? ''),
        ]);
        $this->connector->withMockClient($mock);

        return $mock;
    };
});

describe('RosstatCpiService::fetch', function () {
    it('reads the previous month index for every known category', function () {
        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('082026', 'август 2026', [
                'Все товары и услуги' => 99.92,
                'Продовольственные товары' => 99.74,
                'Мясо и птица' => 101.46,
                'Жилищные и коммунальные услуги (включая аренду квартир)' => 100.26,
                'Услуги телекоммуникационные' => 98.84,
            ]),
        ]));

        $records = $this->service->fetch(Carbon::create(2026, 8, 1), Carbon::create(2026, 8, 31));

        expect($records)->toHaveCount(5)
            ->and($records->pluck('value', 'categoryCode')->all())->toBe([
                'TOTAL' => 99.92,
                'FOOD' => 99.74,
                'FOOD_MEAT' => 101.46,
                'SERVICES_HOUSING' => 100.26,
                'SERVICES_TELECOM' => 98.84,
            ])
            ->and($records->first()->period->toDateString())->toBe('2026-08-01')
            ->and($records->pluck('source')->unique()->all())->toBe([CpiValue::SOURCE_ROSSTAT_WORKBOOK]);
    });

    it('downloads the newest workbook linked on the page', function () {
        $mock = ($this->serve)(rosstatPage('ipc_spr_07-2026.xlsx', 'ipc_spr_09-2026.xlsx', 'ipc_spr_12-2025.xlsx'), rosstatWorkbook([
            rosstatSheet('092026', 'сентябрь 2026', ['Все товары и услуги' => 100.5]),
        ]));

        $this->service->fetch(Carbon::create(2026, 9, 1), Carbon::create(2026, 9, 30));

        $mock->assertSent(fn ($request) => $request instanceof DownloadWorkbookRequest
            && $request->path === '/storage/mediabank/ipc_spr_09-2026.xlsx');
    });

    it('reads a sheet whose name has the typo Rosstat uses for September 2025', function () {
        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('0922025', 'сентябрь 2025', ['Все товары и услуги' => 100.34]),
        ]));

        $records = $this->service->fetch(Carbon::create(2025, 9, 1), Carbon::create(2025, 9, 30));

        expect($records)->toHaveCount(1)
            ->and($records->first()->period->toDateString())->toBe('2025-09-01');
    });

    it('returns only the months of the requested range', function () {
        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('062026', 'июнь 2026', ['Все товары и услуги' => 100.87]),
            rosstatSheet('072026', 'июль 2026', ['Все товары и услуги' => 100.54]),
            rosstatSheet('082026', 'август 2026', ['Все товары и услуги' => 99.92]),
        ]));

        $records = $this->service->fetch(Carbon::create(2026, 7, 10), Carbon::create(2026, 8, 20));

        expect($records->map(fn ($r) => $r->period->format('Y-m'))->sort()->values()->all())->toBe(['2026-07', '2026-08']);
    });

    it('returns nothing when the workbook has no sheet for the range', function () {
        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('082026', 'август 2026', ['Все товары и услуги' => 99.92]),
        ]));

        expect($this->service->fetch(Carbon::create(2023, 1, 1), Carbon::create(2023, 12, 31)))->toBeEmpty();
    });

    it('ignores unknown titles, non numeric values and repeated titles', function () {
        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('082026', 'август 2026', [
                'Кефир, л' => 101.0,
                'Услуги' => 'x',
                'Медикаменты' => 100.2,
                'Медикаменты, кроме ЖНВЛП' => 100.18,
            ]),
        ]));

        $records = $this->service->fetch(Carbon::create(2026, 8, 1), Carbon::create(2026, 8, 31));

        expect($records->pluck('value', 'categoryCode')->all())->toBe(['NON_FOOD_MEDICINE' => 100.2]);
    });

    it('skips a sheet whose title month does not match its name', function () {
        Log::spy();

        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('072026', 'август 2026', ['Все товары и услуги' => 1.0]),
            rosstatSheet('082026', 'август 2026', ['Все товары и услуги' => 99.92]),
        ]));

        $records = $this->service->fetch(Carbon::create(2026, 7, 1), Carbon::create(2026, 8, 31));

        expect($records)->toHaveCount(1)
            ->and($records->first()->value)->toBe(99.92);

        Log::shouldHaveReceived('warning')->once();
    });

    it('fails when the page links no monthly workbook', function () {
        ($this->serve)('<html><body><a href="/storage/mediabank/ipc_mes_08-2026.xlsx">XLSX</a></body></html>');

        $this->service->fetch(Carbon::create(2026, 8, 1), Carbon::create(2026, 8, 31));
    })->throws(RuntimeException::class, 'does not link a monthly CPI workbook');

    it('fails when the previous month column is missing', function () {
        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            array_merge(rosstatSheet('082026', 'август 2026', ['Все товары и услуги' => 99.92]), ['header' => 'к декабрю предыдущего года']),
        ]));

        $this->service->fetch(Carbon::create(2026, 8, 1), Carbon::create(2026, 8, 31));
    })->throws(RuntimeException::class, 'unexpected layout');

    it('fails when Rosstat responds with an error', function () {
        $this->connector->withMockClient(new MockClient([
            GetPricesPageRequest::class => MockResponse::make('forbidden', 403),
        ]));

        $this->service->fetch(Carbon::create(2026, 8, 1), Carbon::create(2026, 8, 31));
    })->throws(RequestException::class);

    it('removes the temporary workbook file', function () {
        $before = glob(sys_get_temp_dir().'/rosstat-cpi-*') ?: [];

        ($this->serve)(rosstatPage('ipc_spr_08-2026.xlsx'), rosstatWorkbook([
            rosstatSheet('082026', 'август 2026', ['Все товары и услуги' => 99.92]),
        ]));

        $this->service->fetch(Carbon::create(2026, 8, 1), Carbon::create(2026, 8, 31));

        expect(glob(sys_get_temp_dir().'/rosstat-cpi-*') ?: [])->toBe($before);
    });
});

describe('RosstatConnector', function () {
    it('verifies TLS against the given CA bundle', function () {
        $connector = new RosstatConnector(caBundle: resource_path('certs/russian-trusted-ca.pem'));

        expect($connector->config()->get('verify'))->toBe(resource_path('certs/russian-trusted-ca.pem'));
    });

    it('keeps the default verification when the bundle file is missing', function () {
        $connector = new RosstatConnector(caBundle: '/nonexistent/bundle.pem');

        expect($connector->config()->get('verify'))->toBeNull();
    });

    it('ships a bundle with the root and both intermediate certificates', function () {
        $bundle = file_get_contents(resource_path('certs/russian-trusted-ca.pem'));
        preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $bundle, $certificates);

        $subjects = array_map(fn (string $pem) => openssl_x509_parse($pem)['subject']['CN'], $certificates[0]);

        expect($subjects)->toBe(['Russian Trusted Root CA', 'Russian Trusted Sub CA', 'Russian Trusted Sub CA']);
    });
});
