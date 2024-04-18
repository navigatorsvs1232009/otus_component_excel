<?php

namespace Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Models\SCM\CompanyCurrencyRatesPropertyValuesTable;

abstract class CompanyCurrencyRatesRepository extends AbstractIblockRepository
{
    const CACHE_DIR = '/company_currency_rates';
    const IBLOCK_ID = COMPANY_CURRENCY_RATES_IBLOCK_ID;

    const OPERATING_CURRENCY = 'USD';

    /**
     * @param  int  $companyId
     * @param  string  $srcCurrency
     * @param  string  $dstCurrency
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getActual(int $companyId, string $srcCurrency, string $dstCurrency): ?array
    {
        if ($srcCurrency === $dstCurrency) {
            return [
                'ID'   => null,
                'RATE' => 1,
            ];
        }

        $rate = CompanyCurrencyRatesPropertyValuesTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', 'SOURCE_CURRENCY', 'DESTINATION_CURRENCY', 'COMPANY_ID', 'ACTIVE_FROM' => 'ELEMENT.ACTIVE_FROM', 'RATE'],
            'filter' => [
                [
                    'COMPANY_ID' => $companyId,
                    [
                        'LOGIC' => 'OR',
                        [
                            '=SOURCE_CURRENCY'      => $srcCurrency,
                            '=DESTINATION_CURRENCY' => $dstCurrency,
                        ],
                        [
                            '=SOURCE_CURRENCY'      => $dstCurrency,
                            '=DESTINATION_CURRENCY' => $srcCurrency,
                        ],
                    ],
                ],
            ],
            'order' => ['ACTIVE_FROM' => 'desc'],
            'limit' => 1
        ])->fetch();

        if ($rate && empty($rate['RATE'])) {
            return null;
        }

        $rate['RATE'] = (float) $rate['RATE'];

        # нашли обратный курс => пересчитаем
        if ($rate && $rate['SOURCE_CURRENCY'] === $dstCurrency) {
            $rate['SOURCE_CURRENCY'] = $srcCurrency;
            $rate['DESTINATION_CURRENCY'] = $dstCurrency;
            $rate['RATE'] = 1 / $rate['RATE'];
        }

        return $rate ?? null;
    }
}
