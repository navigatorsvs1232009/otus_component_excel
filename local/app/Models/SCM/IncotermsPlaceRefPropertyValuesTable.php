<?php

namespace Models\SCM;

use Models\AbstractIblockPropertyValuesTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;

class IncotermsPlaceRefPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    public const IBLOCK_ID = INCOTERMS_PLACE_REF_IBLOCK_ID;

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
                'COUNTRY' => new ReferenceField(
                    'COUNTRY',
                    ElementTable::class,
                    ['=this.COUNTRY_ID' => 'ref.ID']
                )
            ];
    }
}
