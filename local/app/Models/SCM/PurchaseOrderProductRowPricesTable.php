<?php

namespace Models\SCM;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;

\Bitrix\Main\Loader::includeModule('crm');

class PurchaseOrderProductRowPricesTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_procurement_product_row_prices';
    }

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ROW_ID'      => new IntegerField('ROW_ID', [
                'primary' => true,
            ]),
            'CONTRACT_ID' => new IntegerField('CONTRACT_ID', [ //todo переименовать в SOPO_ID
                'primary' => true,
            ]),
            'SOPO_ID'     => new IntegerField('CONTRACT_ID'),

            'MARGIN'          => new FloatField('MARGIN'),
            'PRICE'           => new FloatField('PRICE'),
            'VAT_INCLUDED'    => new BooleanField('VAT_INCLUDED', ['values' => [0, 1]]),
            'VAT'             => new IntegerField('VAT'),
            'CURRENCY_COURSE' => new FloatField('CURRENCY_COURSE'),

            'ROW' => new ReferenceField(
                'ROW',
                ProductRowTable::class,
                ['=this.ROW_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

            'SOPO' => new ReferenceField(
                'SOPO',
                SopoPropertyValueTable::class,
                ['=this.CONTRACT_ID' => 'ref.IBLOCK_ELEMENT_ID']
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
