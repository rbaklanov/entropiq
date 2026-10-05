<?php

namespace App\Integrations\Emiss;

final class EmissCpiCatalog
{
    public const INDICATOR_ID = 31074;

    public const FILTER_INDICATOR = 0;

    public const FILTER_YEAR = 3;

    public const FILTER_KIND = 57937;

    public const FILTER_GOODS = 58273;

    public const FILTER_TERRITORY = 57831;

    public const FILTER_UNIT = 30611;

    public const FILTER_PERIOD = 33560;

    public const KIND_PREVIOUS_MONTH = 1704142;

    public const UNIT_PERCENT = 950473;

    public const TERRITORY_RUSSIA = 1688487;

    public const TERRITORY_RUSSIA_SINCE_2023 = 1849012;

    public const FIRST_YEAR_WITH_NEW_TERRITORIES = 2023;

    /** @var array<int, int> month number => EMISS period id */
    public const MONTHS = [
        1 => 1540283,
        2 => 1540282,
        3 => 1540236,
        4 => 1540229,
        5 => 1540235,
        6 => 1540234,
        7 => 1540233,
        8 => 1540228,
        9 => 1540276,
        10 => 1540273,
        11 => 1540272,
        12 => 1540230,
    ];

    /** @var array<string, array<int, array{id: int, title: string}>> */
    public const CATEGORIES = [
        'TOTAL' => [
            ['id' => 1707675, 'title' => 'Все товары и услуги'],
        ],
        'FOOD' => [
            ['id' => 1748984, 'title' => 'Продовольственные товары'],
        ],
        'FOOD_MEAT' => [
            ['id' => 1788777, 'title' => 'Мясо и птица'],
        ],
        'FOOD_DAIRY' => [
            ['id' => 1750671, 'title' => 'Молоко и молочная продукция'],
        ],
        'FOOD_BREAD' => [
            ['id' => 1788866, 'title' => 'Хлеб и хлебобулочные изделия'],
        ],
        'FOOD_FRUIT' => [
            ['id' => 1788850, 'title' => 'Плодоовощная продукция, включая картофель'],
        ],
        'NON_FOOD' => [
            ['id' => 1744144, 'title' => 'Непродовольственные товары'],
        ],
        'NON_FOOD_CLOTHING' => [
            ['id' => 1788783, 'title' => 'Одежда и белье'],
        ],
        'NON_FOOD_MEDICINE' => [
            ['id' => 1788823, 'title' => 'Медикаменты'],
        ],
        'NON_FOOD_ELECTRONICS' => [
            ['id' => 1788755, 'title' => 'Электротовары и другие бытовые приборы'],
        ],
        'SERVICES' => [
            ['id' => 1744147, 'title' => 'Услуги'],
        ],
        'SERVICES_HOUSING' => [
            ['id' => 1849934, 'title' => 'Жилищные и коммунальные услуги (включая аренду квартир)'],
        ],
        'SERVICES_TRANSPORT' => [
            ['id' => 1788843, 'title' => 'Услуги пассажирского транспорта'],
        ],
        'SERVICES_TELECOM' => [
            ['id' => 1788722, 'title' => 'Услуги связи'],
            ['id' => 1848536, 'title' => 'Услуги телекоммуникационные'],
        ],
        'SERVICES_EDUCATION' => [
            ['id' => 1788810, 'title' => 'Услуги образования'],
        ],
        'SERVICES_HEALTH' => [
            ['id' => 1788721, 'title' => 'Медицинские услуги'],
        ],
        'SERVICES_RECREATION' => [
            ['id' => 1788849, 'title' => 'Услуги организаций культуры'],
        ],
        'SERVICES_CATERING' => [
            ['id' => 1788728, 'title' => 'Общественное питание'],
        ],
    ];

    public static function territoryForYear(int $year): int
    {
        return $year >= self::FIRST_YEAR_WITH_NEW_TERRITORIES
            ? self::TERRITORY_RUSSIA_SINCE_2023
            : self::TERRITORY_RUSSIA;
    }

    public static function categoryCodeByTitle(string $title): ?string
    {
        foreach (self::CATEGORIES as $code => $items) {
            foreach ($items as $item) {
                if ($item['title'] === $title) {
                    return $code;
                }
            }
        }

        return null;
    }

    public static function monthByPeriodId(int $periodId): ?int
    {
        $month = array_search($periodId, self::MONTHS, true);

        return $month === false ? null : $month;
    }
}
