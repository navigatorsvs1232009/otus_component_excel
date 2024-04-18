<?php

namespace Models\SCM;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

Loader::includeModule('crm');

class RPricePropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = RPRICE_IBLOCK_ID;

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
            'PRODUCTS' => new ReferenceField(
                'PRODUCTS',
                RfqProductRowsPropertyValuesTable::class,
                ['=this.IBLOCK_ELEMENT_ID' => 'ref.RPRICE_ID']
            ),

            'STATUS' => new ReferenceField(
                'STATUS',
                ElementTable::class,
                ['=this.STATUS_ID' => 'ref.ID']
            ),

            'SHIPMENT_TERMS_LIST' => new ReferenceField(
                'SHIPMENT_TERMS_LIST',
                PropertyEnumerationTable::class,
                ['=this.SHIPMENT_TERMS' => 'ref.ID']
            ),

            'DELIVERY_METHOD_LIST' => new ReferenceField(
                'DELIVERY_METHOD_LIST',
                PropertyEnumerationTable::class,
                ['=this.DELIVERY_METHOD' => 'ref.ID']
            ),

            'TYPE' => new ReferenceField(
                'TYPE',
                ElementTable::class,
                ['=this.TYPE_ID' => 'ref.ID']
            )
        ];
    }
}
