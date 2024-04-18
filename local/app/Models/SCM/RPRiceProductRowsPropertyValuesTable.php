<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Models\AbstractIblockPropertyValuesTable;
use Models\ProductPropertyValueTable;

class RPRiceProductRowsPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = RPRICE_PRODUCT_ROWS_IBLOCK_ID;

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        $map = parent::getMap();

        $map['BLOCKED'] = new StringField($map['BLOCKED']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return !empty($value);
                    },
                ];
            },
        ]);

        $map['NO_DATA'] = new \Bitrix\Main\ORM\Fields\StringField($map['NO_DATA']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return !empty($value);
                    },
                ];
            },
        ]);

        $map += [
            'RPRICE' => new ReferenceField(
                'RPRICE',
                RPricePropertyValuesTable::class,
                ['=this.RPRICE_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'PRODUCT' => new ReferenceField(
                'PRODUCT',
                ProductPropertyValueTable::class,
                ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'RFQ_PROPERTY' => new ReferenceField(
                'RFQ_PROPERTY',
                RfqPropertyValuesTable::class,
                ['=this.RFQ_ID' => 'ref.IBLOCK_ELEMENT_ID']
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

            'STATUS' => new ReferenceField(
                'STATUS',
                ElementTable::class,
                ['=this.STATUS_ID' => 'ref.ID']
            ),

            'RESPONSIBLE_USER' => new ReferenceField(
                'RESPONSIBLE_USER',
                UserTable::class,
                ['=this.RESPONSIBLE_USER_ID' => 'ref.ID']
            ),

            'RESPONSIBLE_DEPARTMENT' => new ReferenceField(
                'RESPONSIBLE_DEPARTMENT',
                ElementTable::class,
                ['=this.RESPONSIBLE_DEPARTMENT_ID' => 'ref.ID']
            ),

            'STATUS_ENUM' => new ReferenceField(
                'STATUS_ENUM',
                PropertyEnumerationTable::class,
                ['=this.STATUS_ENUM_ID' => 'ref.ID']
            ),

            'SUPPLY_AREA' => new ReferenceField(
                'SUPPLY_AREA',
                ElementTable::class,
                ['=this.RPRICE_SHIPMENT_TERMS_ID' => 'ref.ID']
            ),

            'PRODUCT_RESPONSIBILITY_GROUP' => new ReferenceField(
                'PRODUCT_RESPONSIBILITY_GROUP',
                ElementTable::class,
                ['=this.PRODUCT_RESPONSIBILITY_GROUP_ID' => 'ref.ID']
            ),

            'RPRICE_SHIPMENT_TERMS' => new ReferenceField(
                'RPRICE_SHIPMENT_TERMS',
                ElementTable::class,
                ['=this.RPRICE_SHIPMENT_TERMS_ID' => 'ref.ID']
            ),

            'RPRICE_TYPE' => new ReferenceField(
                'RPRICE_TYPE',
                ElementTable::class,
                ['=this.RPRICE_TYPE_ID' => 'ref.ID']
            ),
        ];

        return $map;
    }
}
