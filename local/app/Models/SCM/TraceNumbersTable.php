<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;

class TraceNumbersTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_trace_numbers';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'YEAR'        => new IntegerField('YEAR', [
                'primary' => true,
            ]),
            'SUPPLIER_ID' => new IntegerField('SUPPLIER_ID', [
                'primary' => true,
            ]),
            'NUMBER'      => new IntegerField('NUMBER', [
                'primary' => true,
            ]),

            'ROW_ID' => new IntegerField('ROW_ID'),

            'ROW'    => new ReferenceField(
                'ROW',
                ProductRowTable::class,
                ['=this.ROW_ID' => 'ref.ID']
            ),

            'SUPPLIER' => new ReferenceField(
                'SUPPLIER',
                CompanyTable::class,
                ['=this.SUPPLIER_ID' => 'ref.ID']
            ),

            'CANCELLED' => new IntegerField('CANCELLED'),
        ];
    }
}
