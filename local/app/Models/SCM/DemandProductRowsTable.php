<?php

namespace Models\SCM;

use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;

Loader::includeModule('catalog');
Loader::includeModule('crm');

class DemandProductRowsTable extends DataManager
{
    const DEAL_OBJECTIVE_ID = 1;
    const STOCK_PROVISION_OBJECTIVE_ID = 2;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_demand_product_rows';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ROW_ID' => new IntegerField('ROW_ID', [
                'primary' => true,
            ]),
            'ROW'    => new ReferenceField(
                'ROW',
                ProductRowTable::class,
                ['=this.ROW_ID' => 'ref.ID', 'ref.OWNER_TYPE' => ['?', 'L']],
                ['join_type' => 'LEFT']
            ),

            'DEADLINE' => new DateField('DEADLINE'),
            'STOCK_ID' => new IntegerField('STOCK_ID'),
            'STOCK' => new ReferenceField('STOCK', StoreTable::class, ['=this.STOCK_ID' => 'ref.ID']),

            'PROCUREMENT_ID'    => new IntegerField('PROCUREMENT_ID'), //todo deprecated
            'PURCHASE_ORDER_ID' => new IntegerField('PROCUREMENT_ID'),
            'PROCUREMENT'       => new ReferenceField(
                'PROCUREMENT',
                DealTable::class,
                ['=this.PROCUREMENT_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),
            'PURCHASE_ORDER'    => new ReferenceField(
                'PURCHASE_ORDER',
                DealTable::class,
                ['=this.PROCUREMENT_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

            'ORDER_ID'       => new IntegerField('ORDER_ID'),
            'ORDER'          => new ReferenceField(
                'ORDER',
                ElementTable::class,
                ['=this.ORDER_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),
            'ORDER_PROPERTY' => new ReferenceField(
                'ORDER',
                OrderPropertyValueTable::class,
                ['=this.ORDER_ID' => 'ref.IBLOCK_ELEMENT_ID'],
                ['join_type' => 'LEFT']
            ),

            # стадия продублирована здесь, т.к. тп может быть отсоединена от ордера, а стадия должна остаться
            'STAGE_ID'       => new IntegerField('STAGE_ID'),
            'STAGE'          => new ReferenceField(
                'STAGE',
                ElementTable::class,
                ['=this.STAGE_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

            # alter table e_scm_demand_product_rows add column XML_ID varchar(40) null
            # TRACK ID (генерится в CRM)
            'XML_ID' => new StringField('XML_ID'),

            # alter table e_scm_demand_product_rows add column PRODUCTION_APPROVAL_ID int null
            'PRODUCTION_APPROVAL_ID' => new IntegerField('PRODUCTION_APPROVAL_ID'),
            'PRODUCTION_APPROVAL' => new ReferenceField(
                'PRODUCTION_APPROVAL',
                ProductionApprovalTable::class,
                ['=this.PRODUCTION_APPROVAL_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

            'OBJECTIVE_ID'   => new IntegerField('OBJECTIVE_ID'),
            'OBJECTIVE_NAME' => (new ExpressionField('OBJECTIVE_NAME', 'OBJECTIVE_ID'))
                ->addFetchDataModifier(fn($objectiveId) => self::getObjectiveName($objectiveId)),

            # alter table e_scm_demand_product_rows add column ERP_STOCK_PROVISION_ID varchar(12) null
            'ERP_STOCK_PROVISION_ID' => new StringField('ERP_STOCK_PROVISION_ID'),
        ];
    }

    /**
     * @param  int|null  $objectiveId
     *
     * @return string
     */
    private static function getObjectiveName(?int $objectiveId): string
    {
        static $objectiveNames = [
            self::DEAL_OBJECTIVE_ID            => 'Deal',
            self::STOCK_PROVISION_OBJECTIVE_ID => 'Stock provision',
        ];

        return $objectiveNames[$objectiveId] ?? '';
    }
}
