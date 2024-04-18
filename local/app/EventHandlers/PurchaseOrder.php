<?php

namespace EventHandlers;

use Agents\Integration\MiningElement\QA\PurchaseOrderExportAgent;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Models\PurchaseOrderDocumentsChecklistTable;
use Models\SCM\DemandProductRowsTable;
use Services\Infrastructure\SCM\ProcurementDeleteHandler;

abstract class PurchaseOrder
{
    /**
     * @param $dealId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterDelete($dealId): void
    {
        (new ProcurementDeleteHandler())->handle($dealId);
        PurchaseOrderExportAgent::scheduleRunOnce($dealId);
    }

    /**
     * @param $deal
     *
     * @return bool
     */
    public static function onBeforeDelete($deal): bool
    {
        if (!$GLOBALS['USER']->IsAdmin()) {
            # запрещаем удаление ПО не админам
            $GLOBALS['APPLICATION']->ThrowException('Deletion is prohibited');

            return false;
        }

        return true;
    }

    /**
     * @param  array  $purchaseOrder
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterUpdate(array $purchaseOrder): void
    {
        $request = Context::getCurrent()->getRequest();

        if ($request->get('DOCUMENTS_CHECKLIST')
            && $documentsChecklist = json_decode($request->get('DOCUMENTS_CHECKLIST'), true)
        ) {
            self::saveDocumentsChecklist($documentsChecklist);
        }
    }

    /**
     * @param  array  $documentsChecklist
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function saveDocumentsChecklist(array $documentsChecklist): void
    {
        foreach ($documentsChecklist as $stageRows) {
            foreach ($stageRows as $rows) {
                foreach ($rows as $row) {
                    if (PurchaseOrderDocumentsChecklistTable::getCount(['=PURCHASE_ORDER_ID' => $row['PURCHASE_ORDER_ID'], '=DOCUMENT_ID' => $row['DOCUMENT_ID']]) > 0) {
                        PurchaseOrderDocumentsChecklistTable::update(['PURCHASE_ORDER_ID' => $row['PURCHASE_ORDER_ID'], 'DOCUMENT_ID' => $row['DOCUMENT_ID']], $row);
                    } else {
                        PurchaseOrderDocumentsChecklistTable::add($row);
                    }
                }
            }
        }
    }

    /**
     * @param  array  $purchaseOrder
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onBeforeUpdate(array $purchaseOrder): bool
    {
        $request = Context::getCurrent()->getRequest();

        # проверка на соответствие ТЧ ПО входящим в ПО товарным потребностям
        if ($request->get('DEAL_PRODUCT_DATA')
            && $productRows = json_decode($request->get('DEAL_PRODUCT_DATA'), true)
        ) {
            $purchasedDemandProducts = array_column(
                DemandProductRowsTable::query()
                    ->setSelect(['PRODUCT_ID' => 'ROW.PRODUCT_ID', 'QUANTITY'])
                    ->where('PURCHASE_ORDER_ID', $purchaseOrder['ID'])
                    ->registerRuntimeField(null, new ExpressionField('QUANTITY', 'sum(%s)', 'ROW.QUANTITY'))
                    ->setGroup('PRODUCT_ID')
                    ->fetchAll(),
                null,
                'PRODUCT_ID'
            );

            foreach ($productRows as $productRow) {
                if (empty($productRow['PRODUCT_ID'])) {
                    continue;
                }

                if (!isset($purchasedDemandProducts[$productRow['PRODUCT_ID']])) {
                    $purchasedDemandProducts[$productRow['PRODUCT_ID']]['QUANTITY'] = 0;
                }

                $purchasedDemandProducts[$productRow['PRODUCT_ID']]['QUANTITY'] -= $productRow['QUANTITY'];

                if ($purchasedDemandProducts[$productRow['PRODUCT_ID']]['QUANTITY'] < 0) {
                    $GLOBALS['fields']['RESULT_MESSAGE'] = 'Product rows dont match to demand product rows purchased by this PO. Please update page and try again';
                    return false;
                }
            }
        }

        return true;
    }
}
