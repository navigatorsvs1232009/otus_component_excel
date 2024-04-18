<?php

namespace Agents\Integration\MiningElement\QA;

use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Tasks\TaskTable;
use CTasks;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Miningelement\Logger;
use Services\Integration\MiningElement\QA\PurchaseOrderExportService;

Loader::includeModule('tasks');

abstract class PurchaseOrderExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $purchaseOrderId
     *
     * @return string|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function runOnce(int $purchaseOrderId): ?string
    {
        if (!self::hasCompletedSpecificationTask($purchaseOrderId)) {
            return null;
        }

        try {
            (new PurchaseOrderExportService(
                new HttpClient(['verify' => false]),
                Option::get('integration', 'purchase_order_export_to_qa_url'),
                new Logger('purchase_order_export_to_qa.log')
            ))->run($purchaseOrderId);

        } catch (GuzzleException $e) {
            return __METHOD__."({$purchaseOrderId});";
        }

        return null;
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private static function hasCompletedSpecificationTask(int $purchaseOrderId): bool
    {
        return (bool) TaskTable::query()
            ->registerRuntimeField(null, new ReferenceField('ORDER_STAGE', ElementTable::class, ['=this.UF_PROC_ORDER_STAGE' => 'ref.ID']))
            ->setSelect(['ID'])
            ->where('UF_CRM_TASK', "D_{$purchaseOrderId}")
            ->where('ORDER_STAGE.CODE', 'SP')
            ->where('STATUS', CTasks::STATE_COMPLETED)
            ->where('ZOMBIE', 'N')
            ->setLimit(1)
            ->fetch();
    }
}
