<?php

namespace App\Integrations\Emiss\Requests;

use App\Integrations\Emiss\EmissCpiCatalog as Catalog;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;

class GetCpiGridRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(public readonly int $year) {}

    public function resolveEndpoint(): string
    {
        return '/indicator/dataGrid.do';
    }

    /** @return array<string, mixed> */
    protected function defaultQuery(): array
    {
        return ['id' => Catalog::INDICATOR_ID];
    }

    /** @return array<string, string> */
    protected function defaultHeaders(): array
    {
        return ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'];
    }

    protected function defaultBody(): string
    {
        $params = [
            ['lineObjectIds', Catalog::FILTER_INDICATOR],
            ['lineObjectIds', Catalog::FILTER_UNIT],
            ['lineObjectIds', Catalog::FILTER_TERRITORY],
            ['lineObjectIds', Catalog::FILTER_GOODS],
            ['columnObjectIds', Catalog::FILTER_YEAR],
            ['columnObjectIds', Catalog::FILTER_PERIOD],
            ['columnObjectIds', Catalog::FILTER_KIND],
            ['selectedFilterIds', Catalog::FILTER_INDICATOR.'_'.Catalog::INDICATOR_ID],
            ['selectedFilterIds', Catalog::FILTER_YEAR.'_'.$this->year],
            ['selectedFilterIds', Catalog::FILTER_KIND.'_'.Catalog::KIND_PREVIOUS_MONTH],
            ['selectedFilterIds', Catalog::FILTER_TERRITORY.'_'.Catalog::territoryForYear($this->year)],
            ['selectedFilterIds', Catalog::FILTER_UNIT.'_'.Catalog::UNIT_PERCENT],
        ];

        foreach (Catalog::CATEGORIES as $items) {
            foreach ($items as $item) {
                $params[] = ['selectedFilterIds', Catalog::FILTER_GOODS.'_'.$item['id']];
            }
        }

        foreach (Catalog::MONTHS as $periodId) {
            $params[] = ['selectedFilterIds', Catalog::FILTER_PERIOD.'_'.$periodId];
        }

        return collect($params)
            ->map(fn (array $pair) => $pair[0].'='.$pair[1])
            ->implode('&');
    }
}
