<?php

namespace Models\SCM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

class PricePerKiloHistoryTable extends DataManager
{
    /*
CREATE TABLE `e_scm_price_per_kilo_history` (
    `ID` int(11) NOT NULL,
    `MODIFIED_AT` datetime NOT NULL default NOW(),
    `MODIFIED_BY_ID` int(11) NOT NULL,
    `SUPPLIER_PRICE` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `OPERATING_PRICE` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `CURRENCY_RATE` decimal(10, 4) not null,
  PRIMARY KEY (`ID`, `MODIFIED_AT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
    */

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_price_per_kilo_history';
    }

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID'              => new IntegerField('ID', [
                'primary' => true,
            ]),
            'MODIFIED_AT'     => new DateTimeField('MODIFIED_AT', [
                'primary' => true,
            ]),
            'MODIFIED_BY_ID'  => new IntegerField('MODIFIED_BY_ID'),
            'MODIFIED_BY'     => new ReferenceField('MODIFIED_BY', UserTable::class, ['=this.MODIFIED_BY_ID' => 'ref.ID']),
            'SUPPLIER_PRICE'  => new StringField('SUPPLIER_PRICE'),
            'OPERATING_PRICE' => new StringField('OPERATING_PRICE'),
            'CURRENCY_RATE'   => new FloatField('CURRENCY_RATE'),
        ];
    }
}
