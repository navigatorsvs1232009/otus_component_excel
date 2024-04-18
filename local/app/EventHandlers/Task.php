<?php

namespace EventHandlers;

use Agents\Integration\MiningElement\Portal\DemandExportAgent;
use Agents\Integration\MiningElement\Portal\DesignWorkExportAgent;
use Agents\Integration\MiningElement\ERP;
use Agents\Integration\MiningElement\QA;
use Agents\Integration\MiningElement\Tooling\ManufacturedToolingItemExportAgent;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Task\DependenceTable;
use Bitrix\Tasks\Internals\TaskTable;
use CIBlockElement;
use CTaskCommentItem;
use CTaskItem;
use CTasks;
use CForumMessage;
use Models\ProductPropertyValueTable;
use Models\SCM\DesignWorkTable;
use Models\SCM\OrderPropertyValueTable;
use Models\SCM\PaymentDateAdjustmentTable;
use Models\SCM\PaymentPropertyValuesTable;
use Repositories\OrdersRepository;
use Repositories\OrderStagesRef;
use Services\Infrastructure\SCM\DeliveryTasksUpdateHandler;
use Services\Infrastructure\SCM\OrderTasksHandler;
use Services\Infrastructure\SCM\UpdateOrderStageService;
use Models\SCM\DemandProductRowsTable;
use Throwable;

class Task
{
    static array $state = [];
    private static int $processingOrderTaskId = 0;

    /**
     * @param $taskId
     * @param $params
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onBeforeDelete($taskId, $params): bool
    {
        if (OrderTasksHandler::$isRunning) {
            return true;
        }

        $currentTask = self::getTask($taskId);
        if (empty($currentTask['UF_PROC_ORDER_ID'])) {
            return true;
        }

        if (empty($GLOBALS['USER']) || $GLOBALS['USER']->IsAdmin()) {
            return true;
        }

        $GLOBALS['APPLICATION']->ThrowException('Direct deletion of order tasks is not allowed');
        return false;
    }

    /**
     * @param $taskId
     *
     * @param $params
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onBeforeUpdate($taskId, &$params): bool
    {
        self::$state['onBeforeUpdate'][$taskId] = $currentTask = self::getTask($taskId);

        # пропускаем задачи не из ордера
        if (empty($currentTask['UF_PROC_ORDER_ID'])) {
            return true;
        }

        # ДЛ всегда равен плановому окончанию и наоборот
        if (isset($params['END_DATE_PLAN'])) {
            $params['DEADLINE'] = $params['END_DATE_PLAN'];

        } elseif (isset($params['DEADLINE'])) {
            $params['MOVING_DEADLINE'] = true;
            $params['END_DATE_PLAN'] = $params['DEADLINE'];
        }

        # дальше реагируем только на прямое обновление задачи
        $action = Application::getInstance()->getContext()->getRequest()->get('ACTION');
        $taskIdFromRequest = $action[0]['ARGUMENTS']['id'] ?? null;
        if ($taskIdFromRequest && $taskIdFromRequest != $taskId) {
            return true;
        }
        if (OrderTasksHandler::$isRunning) {
            $params['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] = false;
            return true;
        }
        if (self::$processingOrderTaskId == $taskId) {
            return true;
        }

        $isTaskClosing =
            self::$state['onBeforeUpdate'][$taskId]['STATUS'] != CTasks::STATE_COMPLETED
            && isset($params['STATUS']) && $params['STATUS'] == CTasks::STATE_COMPLETED;

        $taskDependence = self::getTaskDependence($taskId);

        # не закрываем задачу, если предыдущая задача не закрыта
        if ($isTaskClosing
            && $taskDependence
            && $taskDependence['PREVIOUS_TASK_STATUS'] != CTasks::STATE_COMPLETED
        ) {
            $GLOBALS['APPLICATION']->ThrowException('Previous task is not completed');
            return false;
        }

        # не закрываем задачу, если есть хоть одна активная подзадача
        if ($isTaskClosing && self::hasActiveSubtasks($taskId)) {
            $GLOBALS['APPLICATION']->ThrowException('Subtask is not completed');
            return false;
        }

        # не закрываем задачу OP если в ордере не заполнены плановые дни стадий PR\PC
        $orderStagesRef = OrderStagesRef::all('CODE');
        if ($isTaskClosing && $currentTask['UF_PROC_ORDER_STAGE'] == $orderStagesRef['OP']['ID']) {
            $orderStagesPlan = OrderPropertyValueTable::getById($currentTask['UF_PROC_ORDER_ID'])->fetch()['STAGES_PLAN'];
            $unfilledStages = '';
            if ($orderStagesPlan[$orderStagesRef['PC']['ID']]['task'] === 'on' && empty($orderStagesPlan[$orderStagesRef['PC']['ID']]['plannedDuration'])) {
                $unfilledStages .= $orderStagesRef['PC']['NAME'] . ' ';
            }
            if ($orderStagesPlan[$orderStagesRef['PR']['ID']]['task'] === 'on' && empty($orderStagesPlan[$orderStagesRef['PR']['ID']]['plannedDuration'])) {
                $unfilledStages .= $orderStagesRef['PR']['NAME'];
            }
            if (!empty($unfilledStages)) {
                $GLOBALS['APPLICATION']->ThrowException("Unable to finish task. Please fill {$unfilledStages} planned days");
                return false;
            }
        }

        # не закрываем задачу OP если в ордере не заполнены поля incoterms place, transport type, route
        if ($params['STATUS'] == CTasks::STATE_COMPLETED
            && $currentTask['UF_PROC_ORDER_STAGE'] == $orderStagesRef['OP']['ID']
            && !self::isFilledOrderRequiredFields($currentTask['UF_PROC_ORDER_ID'])
        ) {
            $GLOBALS['APPLICATION']->ThrowException('Unable to finish task. Please fill order fields first: incoterms place, transport type, route');
            return false;
        }

        # иногда прилетает строка => преобразуем в ДТ
        if (isset($params['START_DATE_PLAN']) && is_string($params['START_DATE_PLAN'])) {
            $params['START_DATE_PLAN'] = new DateTime($params['START_DATE_PLAN']);
        }

        # нельзя чтобы образовался разрыв между датой окончания предыдущей и датой начала тек. задач
        if (isset($params['START_DATE_PLAN'])
            && $taskDependence
            && $params['START_DATE_PLAN']->getTimestamp() > $taskDependence['PREVIOUS_TASK_END_DATE_PLAN']->getTimestamp()
        ) {
            $GLOBALS['APPLICATION']->ThrowException('Task start date can`t be more than previous task end date');
            return false;
        }

        if (isset($params['START_DATE_PLAN'])
            && $taskDependence
            && $params['START_DATE_PLAN']->getTimestamp() < $taskDependence['PREVIOUS_TASK_END_DATE_PLAN']->getTimestamp()
        ) {
            $GLOBALS['APPLICATION']->ThrowException('Task start date can`t be less than previous task end date');
            return false;
        }

        # задача закрывается => переносим плановую дату окончания на текущую
        if (isset($params['STATUS']) && $params['STATUS'] == CTasks::STATE_COMPLETED
            && empty($params['END_DATE_PLAN'])
        ) {
            $params['END_DATE_PLAN'] = $params['DEADLINE'] = new DateTime();
        }

        return true;
    }

    /**
     * @param $taskId
     *
     * @param $params
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onUpdate($taskId, $params): void
    {
        # реагируем только на прямое обновление задачи
        $action = Application::getInstance()->getContext()->getRequest()->get('ACTION');
        $taskIdFromRequest = $action[0]['ARGUMENTS']['id'] ?? null;
        if ($taskIdFromRequest && $taskIdFromRequest != $taskId) {
            return;
        }
        if (OrderTasksHandler::$isRunning) {
            return;
        }
        if (self::$processingOrderTaskId == $taskId) {
            return;
        }

        $currentTask = self::getTask($taskId);
        $isTaskClosing = self::$state['onBeforeUpdate'][$taskId]['STATUS'] != CTasks::STATE_COMPLETED && isset($params['STATUS']) && $params['STATUS'] == CTasks::STATE_COMPLETED;

        # пропускаем задачи не из ордера
        $orderId = (int) $currentTask['UF_PROC_ORDER_ID'];
        if (empty($orderId)) {
            return;
        }

        # изменился статус => обновляем статус сопряжённых сущностей
        if ($currentTask['STATUS'] != self::$state['onBeforeUpdate'][$taskId]['STATUS']) {
            (new UpdateOrderStageService())->run($orderId);

            # обновляем связанные деливери
            $deliveryTasksUpdateHandler = new DeliveryTasksUpdateHandler();
            $deliveryTasks = TaskTable::getList([
                'select' => ['UF_DELIVERY_ID'],
                'filter' => ['UF_PROC_ORDER_ID' => $orderId, '!UF_DELIVERY_ID' => false]
            ])->fetchAll();
            foreach ($deliveryTasks as $task) {
                $deliveryTasksUpdateHandler->run($task['UF_DELIVERY_ID'], false);
            }

            # транслируем стадию ордера в платежи
            $payments = PaymentPropertyValuesTable::query()
                ->setSelect(['ID' => 'IBLOCK_ELEMENT_ID', 'ORDER_STAGE_NAME' => 'ORDER_REF.STAGE_REF.NAME'])
                ->where('ORDER', $orderId)
                ->fetchAll();
            foreach ($payments as $payment) {
                PaymentPropertyValuesTable::update($payment['ID'], ['ORDER_STAGE' => $payment['ORDER_STAGE_NAME']]);
            }
        }

        # шедулим выгрузку демандов
        $orderProductRows = self::getOrderProductRows($orderId);
        $demandIds = array_filter(array_unique(array_column($orderProductRows, 'DEMAND_ID')));
        foreach ($demandIds as $demandId) {
            DemandExportAgent::scheduleRunOnce($demandId);
        }

        $tasksChain = OrdersRepository::getOrderTasksChain($orderId);
        $nextTaskId = $tasksChain[$currentTask['ID']]['NEXT_TASK_ID'] ?? null;

        # шедулим выгрузку design work если задача закрывается
        if ($isTaskClosing) {
            foreach ($orderProductRows as $orderProductRow) {
                if (empty($orderProductRow['DESIGN_WORK_ID'])) {
                    continue;
                }

                DesignWorkExportAgent::scheduleRunOnce($orderProductRow['ID']);
            }
        }

        # шедулим выгрузку произведённых оснасток, если закрывается задача на производство
        if ($isTaskClosing && $currentTask['ORDER_STAGE_CODE'] == 'PR') {
            foreach ($orderProductRows as $orderProductRow) {
                if (empty($orderProductRow['PRODUCT_IS_PATTERN'])) {
                    continue;
                }

                if ($orderProductRow['ELEMENT_ID'] === 'pattern') {
                    continue;
                }

                ManufacturedToolingItemExportAgent::scheduleRunOnce($orderProductRow['PRODUCT_ID']);
            }
        }

        # задача закрывается => добавим коммент с уведомлением в след. таску
        if ($isTaskClosing && isset($nextTaskId)) {
            try {
                CTaskCommentItem::add(
                    CTaskItem::getInstance($nextTaskId, BITRIX_NOTIFICATION_USER),
                    [
                        'AUTHOR_ID'    => BITRIX_NOTIFICATION_USER,
                        'POST_MESSAGE' => 'Previous task has been closed',
                    ]
                );
            } catch (Throwable) {
                # не страшно, если тут что-то пошло не так
            }
        }

        # определим, изменилась ли дата окончания
        $timeShift = $currentTask['END_DATE_PLAN']->getTimestamp() - self::$state['onBeforeUpdate'][$taskId]['END_DATE_PLAN']->getTimestamp();
        # нет => дальше делать нечего
        if ($timeShift === 0) {
            return;
        }

        # пройдёмся по следующим задачам и скорректируем сроки, если потребуется
        $task = $tasksChain[$currentTask['ID']];
        while ($nextTask = $tasksChain[$nextTaskId] ?? false) {
            # если двигается только ДЛ или задача закрывается, скорректируем последующие задачи
            if ($params['MOVING_DEADLINE'] || $isTaskClosing) {

                # образовался разрыв или нахлёст => переносим дату начала след. задачи на дату окончания этой
                if ($nextTask['START_DATE_PLAN']->getTimeStamp() != $task['END_DATE_PLAN']->getTimeStamp()) {
                    $newStartDatePlan = clone $task['END_DATE_PLAN'];

                # просто двигаем дату начала последющей задачи
                } else {
                    $newStartDatePlan = DateTime::createFromTimestamp($nextTask['START_DATE_PLAN']->getTimeStamp() + $timeShift);
                }

                $newEndDatePlan = DateTime::createFromTimestamp($nextTask['END_DATE_PLAN']->getTimeStamp() + $timeShift);
                self::$processingOrderTaskId = $nextTask['ID'];
                $nextTaskItem = new CTaskItem($nextTask['ID'], BITRIX_NOTIFICATION_USER);
                $nextTaskItem->update(
                    [
                        'START_DATE_PLAN' => $newStartDatePlan,
                        'END_DATE_PLAN'   => $newEndDatePlan,
                    ],
                    ['CORRECT_DATE_PLAN_DEPENDENT_TASKS' => false]
                );
                self::$processingOrderTaskId = 0;

                # сохраняем даты обратно в набор, т.к. потребуются дальше
                $tasksChain[$nextTaskId]['START_DATE_PLAN'] = $newStartDatePlan;
                $tasksChain[$nextTaskId]['END_DATE_PLAN'] = $newEndDatePlan;
            }

            $task = $tasksChain[$nextTaskId];
            $nextTaskId = $task['NEXT_TASK_ID'] ?? null;
        }
        $lastTask = $task;

        # запишем в ордер дату окончания последней задачи
        (new CIBlockElement())->Update($orderId, ['ACTIVE_TO' => $lastTask['END_DATE_PLAN']]);

        # обновим все связанные платежи
        $orderStagePayments = self::getOrderStagesPayments($orderId);
        foreach ($tasksChain as $task) {
            # обновим время платежей завязанных на соответствующую стадию ордера
            foreach ($orderStagePayments[$task['UF_PROC_ORDER_STAGE']] as $paymentId => $payment) {
                # пропускаем платежи с фиксированной датой
                if ($payment['FIXED_PAYMENT_DATE']) {
                    continue;
                }

                # пропускаем оплаченные платежи
                list($paidAmount) = explode('|', $payment['PAID_AMOUNT']);
                if ((float) $paidAmount) {
                    continue;
                }

                $paymentDiffDays = ((int) $payment['PAYMENT_DATE_DIFF']).' days';

                # даты совпадают => пропускаем
                $newPreliminaryPaymentDate = clone($task['END_DATE_PLAN']);
                $newPreliminaryPaymentDate = $newPreliminaryPaymentDate->add($paymentDiffDays);

                # учитываем корректировки даты платежа
                $companyDateFieldName = match ((int) $payment['PAYER_COMPANY_ID']) {
                    ME_COMPANY_ID => 'ME_DATE',
                    EG_COMPANY_ID => 'EG_DATE',
                    ES_COMPANY_ID => 'ES_DATE',
                    default       => null
                };
                $paymentDateAdjustment = $companyDateFieldName
                    ? PaymentDateAdjustmentTable::query()
                        ->setSelect([$companyDateFieldName])
                        ->where('DATE', $newPreliminaryPaymentDate)
                        ->fetch()
                    : null;
                if (isset($paymentDateAdjustment[$companyDateFieldName])) {
                    $newPaymentDate = $paymentDateAdjustment[$companyDateFieldName];
                } else {
                    $newPaymentDate = $newPreliminaryPaymentDate;
                }

                if (empty($payment['PAYMENT_DATE']) || $newPaymentDate->getTimestamp() !== $payment['PAYMENT_DATE']->getTimestamp()) {
                    (new CIBlockElement())->Update($paymentId, ['ACTIVE_FROM' => $newPaymentDate]);
                }

                if (empty($payment['PRELIMINARY_PAYMENT_DATE']) || $newPreliminaryPaymentDate->getTimestamp() !== $payment['PRELIMINARY_PAYMENT_DATE']->getTimestamp()) {
                    CIBlockElement::SetPropertyValuesEx(
                        $paymentId,
                        PAYMENTS_IBLOCK_ID,
                        ['PRELIMINARY_PAYMENT_DATE' => $newPreliminaryPaymentDate]
                    );
                }
            }
        }

        # зашедулим выгрузку ПО
        $linkedCrmItemId = current($currentTask['UF_CRM_TASK']);
        if (!empty($linkedCrmItemId) && mb_substr($linkedCrmItemId, 0, 2) === 'D_') {
            $purchaseOrderId = (int) str_replace('D_', '', $linkedCrmItemId);
            ERP\PurchaseOrderExportAgent::scheduleRunOnce($purchaseOrderId);
            QA\PurchaseOrderExportAgent::scheduleRunOnce($purchaseOrderId);
        }
    }

    /**
     * @param  array  $notification
     *
     * @return false
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onBeforeNotification(array &$notification): bool
    {
        # об изменениях в задачах от Bitrix notification service уведомляем только ответственного
        if (($notification['ACTION'] === 'TASK_UPDATE' || $notification['ACTION'] === 'TASK_STATUS_CHANGED_MESSAGE')
            && $notification['arFields']['CREATED_BY'] == BITRIX_NOTIFICATION_USER
            && !in_array($notification['arFields']['RESPONSIBLE_ID'], $notification['arRecipientsIDs'])
        ) {
            return false;
        }

        # уведомление о комментарии в задачах order только при упоминании
        if ($notification['MESSAGE']['FORUM_ID'] == TASK_COMMENT_FORUM_ID) {
            $taskId = str_replace('TASK_', '', $notification['MESSAGE']['XML_ID']);
            $task = self::getTask($taskId);

            if (!empty($task['UF_PROC_ORDER_ID'])) {
                $mentionedUsers = CForumMessage::GetMentionedUserID($notification['MESSAGE']['POST_MESSAGE']);

                if (!in_array(current($notification['arRecipientsIDs']), $mentionedUsers)
                    && current($notification['arRecipientsIDs']) != $task['RESPONSIBLE_ID']
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  int  $taskId
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getTask(int $taskId): ?array
    {
        return TaskTable::query()
            ->setSelect([
                'ID',
                'RESPONSIBLE_ID',
                'START_DATE_PLAN',
                'END_DATE_PLAN',
                'UF_PROC_ORDER_ID',
                'STATUS',
                'UF_PROC_ORDER_STAGE',
                'ORDER_STAGE_CODE' => 'ORDER_STAGE.CODE',
                'UF_CRM_TASK',
            ])
            ->where('ID', $taskId)
            ->registerRuntimeField(null, new ReferenceField(
                    'ORDER_STAGE',
                    ElementTable::class,
                    ['=this.UF_PROC_ORDER_STAGE' => 'ref.ID']
                )
            )
            ->fetch() ?: null;
    }

    /**
     * @param  int  $orderId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getOrderStagesPayments(int $orderId): array
    {
        $dbResult = PaymentPropertyValuesTable::query()
            ->setSelect([
                'ID'           => 'IBLOCK_ELEMENT_ID',
                'PAYMENT_DATE' => 'ELEMENT.ACTIVE_FROM',
                'STAGE',
                'PAYMENT_DATE_DIFF',
                'PAID_AMOUNT',
                'FIXED_PAYMENT_DATE',
                'PAYER_COMPANY_ID',
                'PRELIMINARY_PAYMENT_DATE'
            ])
            ->where('ORDER', $orderId)
            ->exec();
        while ($row = $dbResult->fetch()) {
            $row['PRELIMINARY_PAYMENT_DATE'] = $row['PRELIMINARY_PAYMENT_DATE'] ? Date::createFromPhp(date_create($row['PRELIMINARY_PAYMENT_DATE'])) : null;

            $payments[$row['STAGE']][$row['ID']] = $row;
        }

        return $payments ?? [];
    }

    /**
     * @param  int  $orderId
     *
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    private static function getOrderProductRows(int $orderId): array
    {
        return DemandProductRowsTable::query()
            ->setSelect([
                'ID'                 => 'ROW_ID',
                'ELEMENT_ID'         => 'PRODUCT.ELEMENT_ID',
                'DEMAND_ID'          => 'ROW.OWNER_ID',
                'ORDER_ID'           => 'ORDER_ID',
                'DESIGN_WORK_ID'     => 'DESIGN_WORK.DESIGN_WORK_ID',
                'PRODUCT_ID'         => 'ROW.PRODUCT_ID',
                'PRODUCT_IS_PATTERN' => 'PRODUCT.IS_PATTERN',
            ])
            ->where('ORDER_ID', $orderId)
            ->registerRuntimeField(null, new ReferenceField(
                    'DESIGN_WORK',
                    DesignWorkTable::class,
                    ['=this.ID' => 'ref.ROW_ID']
                )
            )
            ->registerRuntimeField(null, new ReferenceField(
                    'PRODUCT',
                    ProductPropertyValueTable::class,
                    ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
                )
            )
            ->fetchAll();
    }

    /**
     * @param  int  $taskId
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function hasActiveSubtasks(int $taskId): bool
    {
        return (bool) TaskTable::query()
            ->where('PARENT_ID', $taskId)
            ->whereNot('STATUS', CTasks::STATE_COMPLETED)
            ->where('ZOMBIE', 'N')
            ->setLimit(1)
            ->fetch();
    }

    /**
     * @param int $orderId
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function isFilledOrderRequiredFields(int $orderId): bool
    {
        return (bool) OrderPropertyValueTable::query()
            ->where('IBLOCK_ELEMENT_ID', $orderId)
            ->whereNotNull('INCOTERMS_PLACE')
            ->whereNotNull('ROUTE')
            ->whereNotNull('TRANSPORT_TYPE_ID')
            ->fetch();
    }

    /**
     * @param  int  $taskId
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getTaskDependence(int $taskId): ?array
    {
        return DependenceTable::query()
            ->setSelect([
                'TASK_ID',
                'PREVIOUS_TASK_ID'            => 'DEPENDS_ON_ID',
                'PREVIOUS_TASK_STATUS'        => 'DEPENDS_ON.STATUS',
                'PREVIOUS_TASK_END_DATE_PLAN' => 'DEPENDS_ON.END_DATE_PLAN',
            ])
            ->where('TASK_ID', $taskId)
            ->where('DIRECT', 1)
            ->setLimit(1)
            ->fetch() ?: null;
    }
}
