<?php

namespace EventHandlers;

use Agents\Integration\MiningElement\ERP\PurchaseOrderExportAgent;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\SystemException;
use Models\ProductPropertyValueTable;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\SCM\PurchaseOrderPaymentsHandler;
use Services\Infrastructure\SCM\PurchaseOrderProductRowsHandler;

class CrmDealProductRows
{
    public static function onAfterSave($purchaseOrderId, $productRowsFromRequest): void
    {
        EntityChangesLoggingService::run('purchase_order_products', $purchaseOrderId);

        $productRowsFromDb = self::getProductRows($purchaseOrderId);
        $purchaseOrderProductRowsHandler = new PurchaseOrderProductRowsHandler();
        $purchaseOrderProductRowsHandler->saveExtraFields($purchaseOrderId, $productRowsFromRequest, $productRowsFromDb);

        $request = Application::getInstance()->getContext()->getRequest();
        $sopoData = json_decode($request->getPost('DEAL_SOPO_DATA'), true) ?: [];
        $purchaseOrderProductRowsHandler->saveSopoData($sopoData);

        (new PurchaseOrderPaymentsHandler())->run($purchaseOrderId);

        PurchaseOrderExportAgent::scheduleRunOnce($purchaseOrderId);
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @return array
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     */
    private static function getProductRows(int $purchaseOrderId): array
    {
        return ProductRowTable::getList([
            'select' => [
                'ID',
                'SORT',
                'PRODUCT_ID',
                'QUANTITY',
                'TRACE_ID_TYPE' => 'PRODUCT_PROPERTY.PRODUCT_KEY_REF.PREVIEW_TEXT',
            ],
            'filter' => ['OWNER_ID' => $purchaseOrderId, 'OWNER_TYPE' => 'D'],
            'runtime' => [
                new IntegerField('SORT'),
                new ReferenceField(
                    'PRODUCT_PROPERTY',
                    ProductPropertyValueTable::class,
                    ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
                )
            ]
        ])->fetchAll();
    }
}
