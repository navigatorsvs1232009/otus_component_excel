<?php

namespace Models\SCM;

use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\DealTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

Loader::includeModule('crm');
Loader::includeModule('tasks');
Loader::includeModule('catalog');

class OrderPropertyValueTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = ORDERS_IBLOCK_ID;

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        $map = parent::getMap();
        $map['PURCHASE_ORDER_ID'] = new IntegerField($map['PROCUREMENT']->getName());
        $map['STOCK_ID'] = new IntegerField($map['WAREHOUSE']->getName());
        $map['STAGES_PLAN'] = new StringField($map['STAGES_PLAN']->getName(), [
            'fetch_data_modification' => function () {
                return [
                    function ($value) {
                        return json_decode($value, true);
                    },
                ];
            },
        ]);

        $map['ACTUAL_TASK'] = new ReferenceField('ACTUAL_TASK', TaskTable::class, ['=this.ACTUAL_TASK_ID' => 'ref.ID']);
        $map['INCOTERMS_REF'] = new ReferenceField('INCOTERMS_REF', ElementTable::class, ['=this.INCOTERMS' => 'ref.ID']); // todo норм. код свойства
        $map['INCOTERMS_PLACE_REF'] = new ReferenceField('INCOTERMS_PLACE_REF', ElementTable::class, ['=this.INCOTERMS_PLACE' => 'ref.ID']); // todo норм. код свойства
        $map['PURCHASE_ORDER'] = new ReferenceField('PURCHASE_ORDER', DealTable::class, ['=this.PURCHASE_ORDER_ID' => 'ref.ID']);
        $map['STOCK'] = new ReferenceField('STOCK', StoreTable::class, ['=this.STOCK_ID' => 'ref.ID']);
        $map['ROUTE_REF'] = new ReferenceField('ROUTE_REF', ElementTable::class, ['=this.ROUTE' => 'ref.ID']);
        $map['STAGE_REF'] = new ReferenceField('STAGE_REF', ElementTable::class, ['=this.STAGE' => 'ref.ID']);
        $map['TRANSPORT_TYPE'] = new ReferenceField('TRANSPORT_TYPE', ElementTable::class, ['=this.TRANSPORT_TYPE_ID' => 'ref.ID']);

        return $map;
    }
}
