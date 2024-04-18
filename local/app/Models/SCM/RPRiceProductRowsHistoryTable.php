<?php

namespace Models\SCM;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

class RPRiceProductRowsHistoryTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_rprice_product_rows_history';
    }

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'RPRICE_PRODUCT_ROW_ID' => new IntegerField('RPRICE_PRODUCT_ROW_ID', ['primary' => true]),
            'MODIFIED_AT'           => new DateTimeField('MODIFIED_AT', ['primary' => true]),

            'STATUS_ENUM_ID' => new IntegerField('STATUS_ENUM_ID'),
            'STATUS_ENUM'    => new ReferenceField(
                'STATUS_ENUM',
                PropertyEnumerationTable::class,
                ['=this.STATUS_ENUM_ID' => 'ref.ID']
            ),

            'MODIFIED_BY_ID' => new IntegerField('MODIFIED_BY_ID'),
            'MODIFIED_BY'    => new ReferenceField(
                'MODIFIED_BY',
                UserTable::class,
                ['=this.MODIFIED_BY_ID' => 'ref.ID']
            ),

            # alter table e_scm_rprice_product_rows_history add column RESPONSIBLE_USER_ID int null
            'RESPONSIBLE_USER_ID' => new IntegerField('RESPONSIBLE_USER_ID'),
            'RESPONSIBLE_USER'    => new ReferenceField(
                'RESPONSIBLE_USER',
                UserTable::class,
                ['=this.RESPONSIBLE_USER_ID' => 'ref.ID']
            ),
        ];
    }
}
