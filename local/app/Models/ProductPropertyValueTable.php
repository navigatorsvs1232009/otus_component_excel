<?php

namespace Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\SystemException;

class ProductPropertyValueTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = PRODUCT_CATALOG_IBLOCK_ID;

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        $map = parent::getMap();

        $map['USE_PRODUCTION_ID'] = new StringField($map['USE_PRODUCTION_ID']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return ! empty($value);
                    },
                ];
            },
        ]);

        $map['PRODUCT_KEY_REF'] = new ReferenceField(
            'PRODUCT_KEY_REF',
            ElementTable::class,
            ['=this.PRODUCT_KEY_ID' => 'ref.ID']
        );

        $map['HAS_ANALOGS'] = new ExpressionField(
            'HAS_ANALOGS',
            'exists (select IBLOCK_PROPERTY_ID from b_iblock_element_prop_m26 as m where m.IBLOCK_ELEMENT_ID = %s and (IBLOCK_PROPERTY_ID = 357 or IBLOCK_PROPERTY_ID = 358))',
            ['IBLOCK_ELEMENT_ID']
        );

        $map['PRODUCT_KEY'] = new StringField($map['PDM_PRODUCT_KEY']->getName()); // todo: зарефакторить везде не PDM_PRODUCT_KEY

        $map['HAS_KD'] = new StringField($map['HAS_KD']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return !empty($value);
                    },
                ];
            },
        ]);

        return $map;
    }
}
