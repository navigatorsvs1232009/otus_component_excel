<?php

namespace Models\SCM;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

Loader::includeModule('crm');

class CompanyNomenclaturePropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = COMPANY_NOMENCLATURE_IBLOCK_ID;

    /**
     * @return ReferenceField[]
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
                'COMPANY' => new ReferenceField(
                    'COMPANY',
                    CompanyTable::class,
                    ['=this.COMPANY_ID' => 'ref.ID']
                ),
            ];
    }
}
