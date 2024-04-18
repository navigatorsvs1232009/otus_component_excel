<?php

namespace EventHandlers;

use Models\SCM\DemandProductRowsTable;

abstract class Demand
{
    /**
     * @param  int  $demandId
     *
     * @return bool|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function onBeforeDelete(int $demandId): ?bool
    {
        if (self::hasPurchasedProductRows($demandId)) {
            $GLOBALS['APPLICATION']->ThrowException('Demand includes purchased product rows, therefore it could`t be deleted');
            return false;
        }

        return null;
    }

    /**
     * @param  int  $demandId
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private static function hasPurchasedProductRows(int $demandId): bool
    {
        return (bool) DemandProductRowsTable::query()
            ->setSelect(['ROW_ID'])
            ->where('ROW.OWNER_TYPE', 'L')
            ->where('ROW.OWNER_ID', $demandId)
            ->where('PURCHASE_ORDER_ID', '>', 0)
            ->setLimit(1)
            ->fetch();
    }
}
