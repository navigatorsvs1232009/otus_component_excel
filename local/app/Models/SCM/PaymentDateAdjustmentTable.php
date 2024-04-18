<?php

namespace Models\SCM;

use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\UserTable;

class PaymentDateAdjustmentTable extends \Bitrix\Main\Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'e_scm_payment_date_adjustment';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID' => new IntegerField('ID', [
                    'primary'      => true,
                    'autocomplete' => true,
                ]
            ),

            'DATE'        => new DateField('DATE', ['unique' => true]),
            'DAY_OF_WEEK' => new ExpressionField('DAY_OF_WEEK', 'DAYNAME(%s)', 'DATE'),
            'WEEK_NUMBER' => new ExpressionField('WEEK_NUMBER', 'WEEK(%s, 1)', 'DATE'),
            'ME_DATE'     => new DateField('ME_DATE', ['nullable' => true]),
            'EG_DATE'     => new DateField('EG_DATE', ['nullable' => true]),
            'ES_DATE'     => new DateField('ES_DATE', ['nullable' => true]),

            'CREATED_AT'         => new DatetimeField('CREATED_AT'),
            'CREATED_BY_USER_ID' => new IntegerField('CREATED_BY_USER_ID'),
            'CREATED_BY_USER'    => new ReferenceField(
                'CREATED_BY_USER',
                UserTable::class,
                ['=this.CREATED_BY_USER_ID' => 'ref.ID']
            ),

            'MODIFIED_AT'         => new DatetimeField('MODIFIED_AT'),
            'MODIFIED_BY_USER_ID' => new IntegerField('MODIFIED_BY_USER_ID'),
            'MODIFIED_BY_USER'    => new ReferenceField(
                'MODIFIED_BY_USER',
                UserTable::class,
                ['=this.MODIFIED_BY_USER_ID' => 'ref.ID']
            ),
        ];
    }
}
