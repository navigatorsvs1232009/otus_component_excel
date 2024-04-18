<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\MiningElement\Portal\DemandExportAgent;
use Agents\Integration\MiningElement\ERP;
use Agents\Integration\MiningElement\QA;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Main\Engine\CurrentUser;
use CBitrixComponent;
use CIBlockElement;
use CTaskItem;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\ProductPropertyValueTable;
use Models\SCM\DemandProductRowsTable;
use Models\SCM\OrderPropertyValueTable;
use Repositories\OrdersRepository;
use Repositories\OrderStagesRef;
use Repositories\SopoRepository;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\IblockElementNameHandler;
use Services\Infrastructure\SCM\DeliveryTasksUpdateHandler;
use Services\Infrastructure\SCM\OrderProductRowsUpdateHandler;
use Services\Infrastructure\SCM\OrderTasksHandler;
use Services\Infrastructure\SCM\UpdateOrderStageService;
use Throwable;

Loader::includeModule('tasks');

class OrderEventHandler extends AbstractEventHandler
    implements OnBeforeUpdateEventHandlerInterface, OnAfterAddEventHandlerInterface, OnAfterUpdateEventHandlerInterface, OnAfterDeleteEventHandlerInterface,
               OnBeforeDeleteEventHandlerInterface, OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface
{
    private static array $state = [];

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        IblockElementNameHandler::handle($element['ID']);

        if (empty($element['PROPERTY_VALUES'])) {
            return;
        }

        $order = $this->getOrderPropertyValues($element['ID']);
        $sellersSopo = SopoRepository::getSellersSopo($order['PROCUREMENT']);
        $supplierCompanyId = SopoRepository::getSupplierCompanyId($sellersSopo);
        $endBuyerCompanyId = SopoRepository::getEndBuyerCompanyId($sellersSopo);
        $companies = $this->getCompanies([$supplierCompanyId]);

        (new OrderTasksHandler())->run($element['ID']);

        CIBlockElement::SetPropertyValuesEx(
            $element['ID'],
            ORDERS_IBLOCK_ID,
            [
                'SUPPLIER_FOLDER'     => $companies[$supplierCompanyId]['UF_DRIVE_FOLDER'],
                'SOURCE_COMPANY'      => $supplierCompanyId,
                'DESTINATION_COMPANY' => $endBuyerCompanyId,
                'LAST_STAGE_ID'       => OrdersRepository::getOrderLastStageId($element['ID'])
            ]
        );

        ERP\PurchaseOrderExportAgent::scheduleRunOnce($order['PROCUREMENT']);
        QA\PurchaseOrderExportAgent::scheduleRunOnce($order['PROCUREMENT']);

        $orderProductRows = $this->getOrderProductRows($element['ID']);

        if (array_key_exists('onBeforeUpdate', self::$state)) {
            (new OrderProductRowsUpdateHandler())->run($order, self::$state['onBeforeUpdate'][$element['ID']]['PRODUCT_ROWS'], $orderProductRows);
        }

        $demandIds = array_unique(array_column($orderProductRows, 'DEMAND_ID'));
        foreach ($demandIds as $demandId) {
            DemandExportAgent::scheduleRunOnce($demandId);
        }

        # обновляем связанные деливери
        $deliveryTasksUpdateHandler = new DeliveryTasksUpdateHandler();
        $deliveryTasks = TaskTable::getList([
            'select' => ['UF_DELIVERY_ID'],
            'filter' => ['UF_PROC_ORDER_ID' => $order['ID'], '!UF_DELIVERY_ID' => false]
        ])->fetchAll();
        foreach ($deliveryTasks as $task) {
            $deliveryTasksUpdateHandler->run($task['UF_DELIVERY_ID'], false);
        }
    }

    /**
     * @param $element
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeUpdate(&$element): ?bool
    {
        if (empty($element['PROPERTY_VALUES'])) {
            return null;
        }

        self::$state['onBeforeUpdate'][$element['ID']]['PRODUCT_ROWS'] = $this->getOrderProductRows($element['ID']);

        return null;
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterDelete($element): void
    {
        # отцепим товарные позиции
        $orderProductRows = DemandProductRowsTable::getList([
            'select' => ['ID' => 'ROW_ID'],
            'filter' => ['ORDER_ID' => $element['ID']]
        ])->fetchAll();
        foreach ($orderProductRows as $productRow) {
            DemandProductRowsTable::update($productRow['ID'], ['ORDER_ID' => null]);
        }

        # удалим задачи
        $orderTasks = TaskTable::getList([
            'select' => ['ID'],
            'filter' => ['UF_PROC_ORDER_ID' => $element['ID']]
        ])->fetchAll();
        foreach ($orderTasks as $task) {
            try {
                (new CTaskItem($task['ID'], BITRIX_NOTIFICATION_USER))->delete();
            } catch (Throwable $e) {
                //
            }
        }

       # удалим платежи
       foreach (self::$state['onBeforeDelete'][$element['ID']]['orderPayments'] as $payment) {
           CIBlockElement::Delete($payment['ID']);
       }
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterUpdate($element): void
    {
        EntityChangesLoggingService::run('order', $element['ID']);

        $this->onAfterAdd($element);

        (new UpdateOrderStageService())->run($element['ID']);
    }

    /**
     * @param  int  $orderId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getOrderPropertyValues(int $orderId): array
    {
        return OrderPropertyValueTable::getList([
            'select' => [
                'ID'                          => 'IBLOCK_ELEMENT_ID',
                'SOURCE_COMPANY',
                'SOURCE_COMPANY_DRIVE_FOLDER' => 'COMPANY.UF_DRIVE_FOLDER',
                'PROCUREMENT',
                'STOCK_ID',
            ],
            'filter'  => ['IBLOCK_ELEMENT_ID' => $orderId],
            'runtime' => [
                new ReferenceField(
                    'COMPANY',
                    CompanyTable::class,
                    ['=this.SOURCE_COMPANY' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                ),
            ],
        ])->fetch() ?: [];
    }

    /**
     * @param  array  $companyIds
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getCompanies(array $companyIds): array
    {
        if (empty($companyIds)) {
            return [];
        }

        $dbResult = CompanyTable::getList([
            'select' => ['ID', 'UF_DRIVE_FOLDER'],
            'filter' => ['ID' => $companyIds]
        ]);
        while ($row = $dbResult->fetch()) {
            $companies[$row['ID']] = $row;
        }

        return $companies ?? [];
    }

    /**
     * @param  int  $orderId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getOrderProductRows(int $orderId): array
    {
        return ProductRowTable::getList([
            'select'  => [
                'ID',
                'QUANTITY',
                'NAME'        => 'PRODUCT_PROPERTY.ELEMENT.NAME',
                'ELEMENT_ID'  => 'PRODUCT_PROPERTY.ELEMENT_ID',
                'OEM_ARTICLE' => 'PRODUCT_PROPERTY.OEM_ARTICLE',
                'DEADLINE'    => 'DEMAND_PRODUCT_ROW.DEADLINE',
                'DEMAND_ID'   => 'OWNER_ID',
                'ORDER_ID'    => 'DEMAND_PRODUCT_ROW.ORDER_ID',
                'STOCK_ID'    => 'DEMAND_PRODUCT_ROW.STOCK_ID',
            ],
            'filter'  => ['ORDER_ID' => $orderId],
            'runtime' => [
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
            ]
        ])->fetchAll();
    }

    /**
     * @param  int  $elementId
     *
     * @return bool|null
     */
    public function onBeforeDelete(int $elementId): ?bool
    {
        $dbResult = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => PAYMENTS_IBLOCK_ID, 'PROPERTY_ORDER' => $elementId],
            false,
            false,
            ['ID', 'IBLOCK_ID']
        );
        while ($row = $dbResult->Fetch()) {
            $orderPayments[] = $row;
        }
        self::$state['onBeforeDelete'][$elementId]['orderPayments'] = $orderPayments ?? [];

        return null;
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void
    {
        Extension::load('element.lists.order.element_edit');

        $orderStagesRef = OrderStagesRef::all();
        $internationalShippingStage = current(
            array_filter(
                $orderStagesRef,
                fn ($stage) => $stage['CODE'] === 'IS'
            )
        );

        $internationalShippingTask = TaskTable::query()
            ->setSelect(['ID', 'UF_DELIVERY_ID'])
            ->where('UF_PROC_ORDER_ID', $component->arResult['ELEMENT_ID'])
            ->where('UF_PROC_ORDER_STAGE', $internationalShippingStage['ID'])
            ->setLimit(1)
            ->fetch();

        # соберём значения полей
        foreach ($component->arResult['FIELDS'] as &$field) {
            if ($field['CODE'] === 'PROCUREMENT') {
                $purchaseOrderId = $component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUE'] ?: $field['DEFAULT_VALUE'];
            }

            if ($field['CODE'] === 'PARENT_ORDER_ID') {
                $parentOrderId = $component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUE'] ?: null;
            }

            if ($field['CODE'] === 'STAGE') {
                $orderStageSort = $orderStagesRef[$component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUE']]['SORT'] ?: 0;
            }
        }

        # в форме нет значения, попробуем взять из гет-параметров
        $parentOrderId = $parentOrderId ?? (int) $this->request->get('parent_order_id');
        if ($parentOrderId) {
            $parentOrder = OrderPropertyValueTable::getByPrimary($parentOrderId, [
                'select' => [
                    'ROUTE',
                    'INCOTERMS',
                    'INCOTERMS_PLACE',
                    'WAREHOUSE',
                    'TRANSPORT_TYPE_ID',
                    'STOCK_ID',
                ],
            ])->fetch();
        }

        # прокидываем значения в нужные поля
        foreach ($component->arResult['FIELDS'] as &$field) {
            switch ($field['CODE']) {
                case 'STAGES_PLAN':
                case 'CONTRACTORS_CHAIN':
                case 'PAYMENT_PLAN':
                case 'PRODUCT_ROWS':
                    $field['PURCHASE_ORDER_ID'] = $purchaseOrderId ?? null;
                    $field['PARENT_ORDER_ID'] = $parentOrderId;
                    break;
                case 'PARENT_ORDER_ID':
                    $field['DEFAULT_VALUE'] = $parentOrderId;
                    break;
                case 'WAREHOUSE':
                case 'ROUTE':
                case 'INCOTERMS_PLACE':
                case 'INCOTERMS':
                case 'TRANSPORT_TYPE_ID':
                    if ($internationalShippingTask && !CurrentUser::get()->isAdmin()
                        && (
                            (isset($orderStageSort) && $orderStageSort > $internationalShippingStage['SORT'])
                            || $internationalShippingTask['UF_DELIVERY_ID'] > 0
                        )
                    ) {
                        $field['SETTINGS']['EDIT_READ_ONLY_FIELD'] = 'Y';
                    }

                    break;
            }

            if (isset($parentOrder[$field['CODE']])) {
                $field['DEFAULT_VALUE'] = $parentOrder[$field['CODE']];
            }
        }
    }
}
