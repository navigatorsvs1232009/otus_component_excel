<?php

namespace Repositories;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Models\SCM\CompanySupplyAreaPropertyValuesTable;

abstract class CompanySupplyAreasRepository extends AbstractIblockRepository
{
    const CACHE_DIR = '/company_supply_areas';
    const IBLOCK_ID = COMPANY_SUPPLY_AREAS_IBLOCK_ID;

    /**
     * @param  int|null  $companyId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getSupplierIncotermsPlaces(?int $companyId): array
    {
        if (empty($companyId)) {
            return [];
        }

        $incotermsPlaces = [];
        $dbResult = CompanySupplyAreaPropertyValuesTable::getList([
            'select'  => ['ID' => 'INCOTERMS_PLACE_ID', 'NAME' => 'INCOTERMS_PLACE.NAME'],
            'filter'  => ['COMPANY_ID' => $companyId],
            'runtime' => [
                'INCOTERMS_PLACE' => new ReferenceField(
                    'INCOTERMS_PLACE',
                    ElementTable::class,
                    ['=this.ID' => 'ref.ID']
                )
            ],
            'order'   => ['NAME' => 'ASC']
        ]);
        while ($row = $dbResult->fetch()) {
            $incotermsPlaces[$row['ID']] = $row;
        }

        return array_values($incotermsPlaces);
    }
}
