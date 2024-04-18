<?php

namespace Models\SCM;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

class TransportTimeRefPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = TRANSPORT_TIME_REF_IBLOCK_ID;

    /**
     * @return ReferenceField[]
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
                'INCOTERMS_PLACE' => new ReferenceField(
                    'INCOTERMS_PLACE',
                    ElementTable::class,
                    ['=this.INCOTERMS_PLACE_ID' => 'ref.ID']
                ),

                'TRANSPORT_TYPE' => new ReferenceField(
                    'TRANSPORT_TYPE',
                    ElementTable::class,
                    ['=this.TRANSPORT_TYPE_ID' => 'ref.ID']
                ),

                'SUPPLY_AREA' => new ReferenceField(
                    'SUPPLY_AREA',
                    ElementTable::class,
                    ['=this.SUPPLY_AREA_ID' => 'ref.ID']
                ),
            ];
    }
}
