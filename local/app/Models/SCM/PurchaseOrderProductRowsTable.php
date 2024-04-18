<?php

namespace Models\SCM;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;

class PurchaseOrderProductRowsTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_purchase_order_product_rows';
    }

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ROW_ID' => new IntegerField('ROW_ID', [
                'primary' => true,
            ]),
            'OSCAR_SALES_ORDER_ROW_ID' => new IntegerField('OSCAR_SALES_ORDER_ROW_ID'),

            'ROW' => new ReferenceField(
                'ROW',
                ProductRowTable::class,
                ['=this.ROW_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

        ];
    }

    /**
     *
     */
    public static function gc(): void
    {
        $queryStr = 'DELETE FROM '.self::getTableName().
            ' WHERE NOT EXISTS (select ID from '.ProductRowTable::getTableName().' where '.ProductRowTable::getTableName().'.ID = '.self::getTableName().'.ROW_ID)';

        try {
            Application::getConnection()->query($queryStr);
        } catch (SqlQueryException $e) {
            return;
        }
    }
}
