<?php

namespace Services\Infrastructure\SCM;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\TaskTable;
use CIBlockElement;
use CTasks;
use Models\SCM\DemandProductRowsTable;
use Repositories\OrderStagesRef;
use Throwable;

Loader::includeModule('tasks');

/**
 * Class UpdateOrderStageService
 *
 * @package Services\Infrastructure\SCM
 */
class UpdateOrderStageService
{
    private array $orderStagesRef;

    /**
     * UpdateOrderStageService constructor.
     */
    public function __construct()
    {
        $this->orderStagesRef = OrderStagesRef::all('CODE');
    }

    /**
     * @param  int  $orderId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function run(int $orderId): void
    {
        $orderRunningTasks = $this->getOrderRunningTasks($orderId);
        $orderActualTask = $this->getActualTask($orderRunningTasks);

        CIBlockElement::SetPropertyValuesEx(
            $orderId,
            ORDERS_IBLOCK_ID,
            ['STAGE' => $orderActualTask['UF_PROC_ORDER_STAGE'], 'ACTUAL_TASK_ID' => $orderActualTask['ID']]
        );

        try {
            Application::getConnection()
                ->query("update ".DemandProductRowsTable::getTableName()." set STAGE_ID={$orderActualTask['UF_PROC_ORDER_STAGE']} where ORDER_ID={$orderId}");
        } catch (Throwable $e) {
            #
        }

    }

    /**
     * @param  array  $orderRunningTasks
     *
     * @return array
     */
    private function getActualTask(array $orderRunningTasks): array
    {
        foreach ($this->orderStagesRef as $stage) {
            if (empty($orderRunningTasks[$stage['ID']])) {
                continue;
            }

            if (isset($orderRunningTasks[$stage['ID']])) {
                return $orderRunningTasks[$stage['ID']];
            }
        }

        return [
            'UF_PROC_ORDER_STAGE' => $this->orderStagesRef['FF']['ID'],
            'ACTUAL_TASK_ID'      => '',
        ];
    }

    /**
     * @param  int  $orderId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getOrderRunningTasks(int $orderId):array
    {
        $dbResult = TaskTable::getList([
            'select' => ['ID', 'UF_PROC_ORDER_STAGE', 'STATUS'],
            'filter' => [
                '=UF_PROC_ORDER_ID' => $orderId,
                '!=STATUS'          => CTasks::STATE_COMPLETED,
                '=ZOMBIE'           => 'N',
            ],
        ]);
        while ($row = $dbResult->fetch()) {
            $orderRunningTasks[$row['UF_PROC_ORDER_STAGE']] = $row;
        }

        return $orderRunningTasks ?? [];
    }
}
