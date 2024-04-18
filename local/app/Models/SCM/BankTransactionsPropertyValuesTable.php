<?php

namespace Models\SCM;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

class BankTransactionsPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = BANK_TRANSACTIONS_IBLOCK_ID;

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        $map = parent::getMap();

        $map['PAYMENT_IDS'] = new StringField($map['PAYMENT_IDS']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return json_decode($value, true) ?: [];
                    },
                ];
            },
        ]);

        return $map;
    }
}
