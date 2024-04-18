<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

class SopoPropertyValueTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = SOPO_IBLOCK_ID;
    const BUYER_PROPERTY_ID = 91; // todo: убрать
    const SELLER_PROPERTY_ID = 92; // todo: убрать
    const INVOICE_NUMBER_PROPERTY_ID = 100; // todo: убрать
    const PURCHASE_ORDER_ID_PROPERTY_ID = 88; // todo: убрать

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        $map = parent::getMap();

        $map['INVOICE_NUMBER'] = new StringField($map['INVOICE_NUMBER']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return json_decode($value, true);
                    },
                ];
            },
        ]);

        $map += [
            //'PROCUREMENT'            => new IntegerField('PROPERTY_88'), // todo deprecated - переименовать
            'PURCHASE_ORDER_ID'      => new IntegerField($map['PROCUREMENT']->getName()),

            //'BUYER'                  => new IntegerField('PROPERTY_91'), // todo deprecated - переименовать
            'BUYER_COMPANY_ID'       => new IntegerField($map['BUYER']->getName()),
            'BUYER_COMPANY'          => new ReferenceField('BUYER_COMPANY', CompanyTable::class, ['=this.BUYER_COMPANY_ID' => 'ref.ID']),

            //'SELLER'                 => new IntegerField('PROPERTY_92'), // todo deprecated - переименовать
            'SELLER_COMPANY_ID'      => new IntegerField($map['SELLER']->getName()),
            'SELLER_COMPANY' => new ReferenceField('SELLER_COMPANY', CompanyTable::class, ['=this.SELLER_COMPANY_ID' => 'ref.ID']),

            'SUPPLY_CONTRACT_NUMBER' => new StringField($map['CONTRACT']->getName())
        ];

        return $map;
    }
}
