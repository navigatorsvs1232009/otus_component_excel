<?php

namespace Models\SCM;

use Bitrix\Crm\DealTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

class SpecificationPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = SPECIFICATION_IBLOCK_ID;

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public static function getMap(): array
    {
        $map = parent::getMap();

        $map['PRODUCT_ROWS'] = new StringField($map['PRODUCT_ROWS']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return json_decode($value, true) ?: [];
                    },
                ];
            },
        ]);

        $map['PRODUCTION_APPROVED'] = new StringField($map['PRODUCTION_APPROVED']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return !empty($value);
                    },
                ];
            },
        ]);

        $map['PROCUREMENT_APPROVED'] = new StringField($map['PROCUREMENT_APPROVED']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return !empty($value);
                    },
                ];
            },
        ]);

        $map['PURCHASE_ORDER'] = new ReferenceField(
            'PURCHASE_ORDER',
            DealTable::class,
            ['=this.PURCHASE_ORDER_ID' => 'ref.ID']
        );

        $map['INCOTERMS'] = new ReferenceField(
            'INCOTERMS',
            ElementTable::class,
            ['=this.INCOTERMS_ID' => 'ref.ID']
        );

        $map['INCOTERMS_PLACE'] = new ReferenceField(
            'INCOTERMS_PLACE',
            ElementTable::class,
            ['=this.INCOTERMS_PLACE_ID' => 'ref.ID']
        );

        $map['TRANSFER_OF_OWNERSHIP'] = new ReferenceField(
            'TRANSFER_OF_OWNERSHIP',
            ElementTable::class,
            ['=this.TRANSFER_OF_OWNERSHIP_ID' => 'ref.ID']
        );

        $map['PAYMENT_TERMS'] = new ReferenceField(
            'PAYMENT_TERMS',
            ElementTable::class,
            ['=this.PAYMENT_TERMS_ID' => 'ref.ID']
        );

        return $map;
    }
}
