<?php

namespace Controllers\Rest\SCM;

use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Models\ProductPropertyValueTable;
use Models\SCM\DemandProductRowsTable;
use Repositories\OrderStagesRef;

class ProductsTransitController
{
    /**
     * @param  array  $params
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function list(array $params): array
    {
        $orderStagesRef = OrderStagesRef::all('CODE');

        $filter = [
            '=OWNER_TYPE'                                       => 'L',
            '!=DEMAND_PRODUCT_ROW.PURCHASE_ORDER_ID'            => false,
            '!=DEMAND_PRODUCT_ROW.ORDER_PROPERTY.LAST_STAGE_ID' => $orderStagesRef['FF']['ID'],
            '>DELIVERY_DATE_FACT'                               => new DateTime(),
        ];
        if (isset($params['storeGuid'])) {
            $filter['=STOCK_XML_ID'] = $params['storeGuid'];
        }

        $dbResult = ProductRowTable::getList([
            'select' => [
                'PRODUCT_ID',
                'TOTAL_QUANTITY',
                'DELIVERY_DATE_FACT' => 'DEMAND_PRODUCT_ROW.ORDER.ACTIVE_TO',
                'ELEMENT_ID'         => 'PRODUCT_PROPERTY.ELEMENT_ID',
                'STOCK_ID'           => 'DEMAND_PRODUCT_ROW.STOCK_ID',
                'STOCK_XML_ID'       => 'DEMAND_PRODUCT_ROW.STOCK.XML_ID',
            ],
            'filter' => $filter,
            'runtime' => [
                new ExpressionField('TOTAL_QUANTITY', 'sum(%s)', ['QUANTITY']),
                new ReferenceField(
                    'PRODUCT_PROPERTY',
                    ProductPropertyValueTable::class,
                    ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID'],
                    ['join_type' => 'LEFT']
                ),
                new ReferenceField(
                    'DEMAND_PRODUCT_ROW',
                    DemandProductRowsTable::class,
                    ['=this.ID' => 'ref.ROW_ID'],
                    ['join_type' => 'LEFT']
                ),
                new ReferenceField(
                    'STOCK',
                    StoreTable::class,
                    ['=this.DEMAND_PRODUCT_ROW.STOCK_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                ),
            ],
            'group' => ['PRODUCT_ID', 'STOCK_ID', 'DELIVERY_DATE_FACT']
        ]);
        while ($row = $dbResult->fetch()) {
            $productTransits[] = [
                'elementId' => $row['ELEMENT_ID'],
                'storeGuid' => $row['STOCK_XML_ID'],
                'date'      => $row['DELIVERY_DATE_FACT']->format('Y-m-d'),
                'quantity'  => (int) $row['TOTAL_QUANTITY'],
            ];
        }

        return $productTransits ?? [];
    }
}
