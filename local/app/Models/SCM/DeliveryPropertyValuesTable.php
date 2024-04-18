<?php

namespace Models\SCM;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\ReferenceField;
use Models\AbstractIblockPropertyValuesTable;

class DeliveryPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = DELIVERY_IBLOCK_ID;

    /**
     * @return ReferenceField[]
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
                'TRANSPORT_TYPE' => new ReferenceField(
                    'TRANSPORT_TYPE',
                    ElementTable::class,
                    ['=this.TRANSPORT_TYPE_ID' => 'ref.ID']
                ),
            ];
    }
}
