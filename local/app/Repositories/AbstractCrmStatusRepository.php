<?php

namespace Repositories;

use Bitrix\Crm\StatusTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

abstract class AbstractCrmStatusRepository
{
    /**
     * @param string $byKey
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function all(string $byKey = 'STATUS_ID'): array
    {
        $dbResult = StatusTable::query()
            ->setSelect([
                'STATUS_ID',
                'NAME',
                'SORT',
            ])
            ->where('ENTITY_ID', static::ENTITY_ID)
            ->exec();
        while ($row = $dbResult->fetch()) {
            $rows[$row[$byKey]] = $row;
        }

        return $rows ?? [];
    }
}
