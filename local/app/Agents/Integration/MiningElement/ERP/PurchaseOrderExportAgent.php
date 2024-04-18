<?php

namespace Agents\Integration\MiningElement\ERP;

use Agents\ScheduleRunOnceTrait;
use Bitrix\Crm\DealTable as PurchaseOrder;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\TaskTable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Miningelement\Logger;
use Repositories\OrderStagesRef;
use Services\Integration\MiningElement\ERP\PurchaseOrderExportService;

Loader::includeModule('crm');
Loader::includeModule('tasks');

abstract class PurchaseOrderExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $purchaseOrderId
     *
     * @return string|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function runOnce(int $purchaseOrderId): ?string
    {
        $purchaseOrder = PurchaseOrder::getByPrimary($purchaseOrderId, ['select' => ['UF_XML_ID']])->fetch();
        if (empty($purchaseOrder['UF_XML_ID'])) {
            return null;
        }

        # найдём все задачи PO, исключая order processing
        $purchaseOrderTasks = self::getPurchaseOrderTasks($purchaseOrderId);
        # нет задач, нет плана => выходим
        if (empty($purchaseOrderTasks)) {
            return null;
        }

        try {
            (new PurchaseOrderExportService(
                new HttpClient(),
                Option::get('me1c', 'purchase_order_export_to_me1c_url'),
                new Logger('purchase_order_export_to_me1c.log')
            ))->run($purchaseOrderId);

        } catch (RequestException) {
            return __METHOD__."({$purchaseOrderId});";
        }

        return null;
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getPurchaseOrderTasks(int $purchaseOrderId): array
    {
        $orderStagesRef = OrderStagesRef::all('CODE');

        return TaskTable::query()
            ->setSelect(['ID', 'UF_PROC_ORDER_STAGE'])
            ->setFilter([
                '=UF_CRM_TASK'          => "D_{$purchaseOrderId}",
                '=ZOMBIE'               => 'N',
                '!=UF_PROC_ORDER_STAGE' => [$orderStagesRef['OP']['ID'], $orderStagesRef['SP']['ID']],
            ])
            ->fetchAll();
    }
}
