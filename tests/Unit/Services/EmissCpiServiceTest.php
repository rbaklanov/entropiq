<?php

use App\Integrations\Emiss\EmissConnector;
use App\Integrations\Emiss\Requests\GetCpiGridRequest;
use App\Services\EmissCpiService;
use Illuminate\Support\Carbon;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function emissGridPayload(int $year, array $rows): array
{
    return [
        'results' => array_map(fn (array $row) => [
            'dim0' => 'Индексы потребительских цен на товары и услуги',
            'dim30611' => 'процент',
            'dim57831' => 'Российская Федерация без учета новых субъектов (с 01.01.2023)',
            'dim58273' => $row['title'],
            "dim{$year}_1540283_1704142_d1_i1" => $row['jan'],
            "dim{$year}_1540282_1704142_d1_i2" => $row['feb'],
            "dim{$year}_1540222_1704142_d1_i3" => '101,00',
        ], $rows),
        '__count' => (string) count($rows),
    ];
}

beforeEach(function () {
    $this->connector = new EmissConnector;
    $this->service = new EmissCpiService($this->connector);
});

describe('EmissCpiService::fetch', function () {
    it('parses monthly values with decimal commas and skips quarterly columns', function () {
        $this->connector->withMockClient(new MockClient([
            GetCpiGridRequest::class => MockResponse::make(emissGridPayload(2025, [
                ['title' => 'Все товары и услуги', 'jan' => '101,23', 'feb' => '100,81'],
                ['title' => 'Продовольственные товары', 'jan' => '100,5', 'feb' => '100'],
            ])),
        ]));

        $records = $this->service->fetch(Carbon::create(2025, 1, 1), Carbon::create(2025, 12, 31));

        expect($records)->toHaveCount(4);

        $total = $records->first(fn ($r) => $r->categoryCode === 'TOTAL' && $r->period->month === 1);

        expect($total->value)->toBe(101.23)
            ->and($total->period->toDateString())->toBe('2025-01-01');

        $food = $records->first(fn ($r) => $r->categoryCode === 'FOOD' && $r->period->month === 2);

        expect($food->value)->toBe(100.0);
    });

    it('ignores rows with titles that are not in the catalog', function () {
        $this->connector->withMockClient(new MockClient([
            GetCpiGridRequest::class => MockResponse::make(emissGridPayload(2025, [
                ['title' => 'Кефир, л', 'jan' => '101,00', 'feb' => '101,00'],
            ])),
        ]));

        expect($this->service->fetch(Carbon::create(2025, 1, 1), Carbon::create(2025, 12, 31)))->toBeEmpty();
    });

    it('maps both telecom classification titles to one category code', function () {
        $this->connector->withMockClient(new MockClient([
            GetCpiGridRequest::class => MockResponse::make(emissGridPayload(2025, [
                ['title' => 'Услуги телекоммуникационные', 'jan' => '100,13', 'feb' => '99,68'],
            ])),
        ]));

        $records = $this->service->fetch(Carbon::create(2025, 1, 1), Carbon::create(2025, 12, 31));

        expect($records->pluck('categoryCode')->unique()->all())->toBe(['SERVICES_TELECOM']);
    });

    it('limits records to the requested months', function () {
        $this->connector->withMockClient(new MockClient([
            GetCpiGridRequest::class => MockResponse::make(emissGridPayload(2025, [
                ['title' => 'Все товары и услуги', 'jan' => '101,23', 'feb' => '100,81'],
            ])),
        ]));

        $records = $this->service->fetch(Carbon::create(2025, 2, 10), Carbon::create(2025, 2, 20));

        expect($records)->toHaveCount(1)
            ->and($records->first()->period->toDateString())->toBe('2025-02-01');
    });

    it('requests every year of the range once', function () {
        $mock = new MockClient([
            GetCpiGridRequest::class => MockResponse::make(['results' => [], '__count' => '0']),
        ]);
        $this->connector->withMockClient($mock);

        $this->service->fetch(Carbon::create(2024, 11, 1), Carbon::create(2026, 2, 1));

        $mock->assertSentCount(3);
    });

    it('uses the territory valid for the requested year', function () {
        $mock = new MockClient([
            GetCpiGridRequest::class => MockResponse::make(['results' => [], '__count' => '0']),
        ]);
        $this->connector->withMockClient($mock);

        $this->service->fetch(Carbon::create(2022, 1, 1), Carbon::create(2023, 1, 1));

        $mock->assertSent(fn (GetCpiGridRequest $request, $response) => $request->year === 2022
            && str_contains((string) $response->getPendingRequest()->body(), 'selectedFilterIds=57831_1688487'));

        $mock->assertSent(fn (GetCpiGridRequest $request, $response) => $request->year === 2023
            && str_contains((string) $response->getPendingRequest()->body(), 'selectedFilterIds=57831_1849012'));
    });

    it('sends repeated filter parameters as a raw form body', function () {
        $mock = new MockClient([
            GetCpiGridRequest::class => MockResponse::make(['results' => [], '__count' => '0']),
        ]);
        $this->connector->withMockClient($mock);

        $this->service->fetch(Carbon::create(2025, 1, 1), Carbon::create(2025, 1, 1));

        $mock->assertSent(function (GetCpiGridRequest $request, $response) {
            $pending = $response->getPendingRequest();
            $body = (string) $pending->body();

            return $pending->getUri()->getPath() === '/indicator/dataGrid.do'
                && str_contains($pending->getUri()->getQuery(), 'id=31074')
                && substr_count($body, 'selectedFilterIds=33560_') === 12
                && str_contains($body, 'selectedFilterIds=57937_1704142')
                && str_contains($body, 'selectedFilterIds=58273_1707675');
        });
    });

    it('throws when EMISS responds with a server error', function () {
        $this->connector->tries = 1;
        $this->connector->withMockClient(new MockClient([
            GetCpiGridRequest::class => MockResponse::make('error', 503),
        ]));

        $this->service->fetch(Carbon::create(2025, 1, 1), Carbon::create(2025, 1, 1));
    })->throws(RequestException::class);
});
