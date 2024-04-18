<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

Loader::includeModule('crm');

class RfqPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = RFQ_IBLOCK_ID;

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
            'ELEMENT' => new ReferenceField(
                'ELEMENT',
                ElementTable::class,
                ['=this.IBLOCK_ELEMENT_ID' => 'ref.ID']
            ),

            'INCOTERMS' => new ReferenceField(
                'INCOTERMS',
                ElementTable::class,
                ['=this.INCOTERMS_ID' => 'ref.ID']
            ),

            'INCOTERMS_PLACE' => new ReferenceField(
                'INCOTERMS_PLACE',
                ElementTable::class,
                ['=this.INCOTERMS_PLACE_ID' => 'ref.ID']
            ),

            'SUPPLIER' => new ReferenceField(
                'SUPPLIER',
                CompanyTable::class,
                ['=this.SUPPLIER_ID' => 'ref.ID']
            ),

            'PRODUCTS' => new ReferenceField(
                'PRODUCTS',
                RfqProductRowsPropertyValuesTable::class,
                ['=this.ID' => 'ref.RFQ_ID']
            ),

            'INITIAL_RPRICE' => new ReferenceField(
                'INITIAL_RPRICE',
                ElementTable::class,
                ['=this.INITIAL_RPRICE_ID' => 'ref.ID']
            ),

            'STATUS' => new ReferenceField(
                'STATUS',
                ElementTable::class,
                ['=this.STATUS_ID' => 'ref.ID']
            ),

            'EXPORT_TO_1C_ME_TYPE' => new ReferenceField(
                'EXPORT_TO_1C_ME_TYPE',
                PropertyEnumerationTable::class,
                ['=this.EXPORT_TO_1C_ME_TYPE_ENUM_ID' => 'ref.ID']
            )
        ];
    }
}
