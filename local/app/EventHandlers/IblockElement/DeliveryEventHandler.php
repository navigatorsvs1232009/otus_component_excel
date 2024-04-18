<?php

namespace EventHandlers\IblockElement;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;
use CIBlockElement;
use CTaskAssertException;
use CTaskItem;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use Models\SCM\DeliveryPropertyValuesTable;
use Repositories\DeliveriesRepository;
use Services\Domain\Delivery\CostsEurHandler;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\IblockElementNameHandler;
use Services\Infrastructure\SCM\DeliveryLocalDocumentsHandler;
use Services\Infrastructure\SCM\DeliveryTasksUpdateHandler;
use UserTypes\Delivery\TransportExpenses;

class DeliveryEventHandler implements OnAfterAddEventHandlerInterface, OnAfterSetPropertyValuesEventHandlerInterface, OnAfterDeleteEventHandlerInterface, OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface
{
    static ?CBitrixComponent $component = null;

    /**
     * @param $elementId
     * @param $propertyValues
     *
     * @throws ArgumentException
     * @throws LoaderException
     * @throws NotImplementedException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws CTaskAssertException
     */
    public function onAfterSetPropertyValues($elementId, $propertyValues): void
    {
        $delivery = $this->getDelivery($elementId);
        $filesFolder = $this->handleFilesFolder($delivery);

        $transportExpensesPropertyId = DeliveryPropertyValuesTable::getPropertyId('TRANSPORT_EXPENSES');
        TransportExpenses::saveValue(
            $elementId,
            $propertyValues[$transportExpensesPropertyId] ?: [],
            Context::getCurrent()->getRequest()->getFile("PROPERTY_$transportExpensesPropertyId") ?: [],
            $filesFolder
        );

        # обновили набор задач => запускаем хандлер
        if (array_key_exists('TASK_ID', $propertyValues)
            || array_key_exists(DeliveryPropertyValuesTable::getPropertyId('TASK_ID'), $propertyValues)
        ) {
            (new DeliveryTasksUpdateHandler())->run($elementId);
        }

        $this->handleFiles(current($delivery['CUSTOMS_DOCUMENT_FILE_ID']), $filesFolder);

        $this->handleContainersQuantity($delivery);

        IblockElementNameHandler::handle($elementId);

        EntityChangesLoggingService::run('Delivery', $elementId, 'update');
    }

    /**
     * @param $element
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        IblockElementNameHandler::handle($element['ID']);
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function onAfterDelete($element): void
    {
        $deliveryTasks = DeliveriesRepository::getDeliveryTasks($element['ID']);
        foreach ($deliveryTasks as $task) {
            (new CTaskItem($task['ID'], BITRIX_NOTIFICATION_USER))->update(['UF_DELIVERY_ID' => null]);
        }

        EntityChangesLoggingService::run('Delivery', $element['ID'], 'delete');
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @throws LoaderException
     */
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void
    {
        self::$component = $component;
        Extension::load('element.lists.delivery.element_edit');
    }

    /**
     * @param  array  $delivery
     *
     * @return Folder|null
     * @throws NotImplementedException
     */
    private function handleFilesFolder(array $delivery): ?Folder
    {
        $folder = $delivery['FILES_FOLDER_ID']
            ? Folder::load(['=ID' => $delivery['FILES_FOLDER_ID']])
            : Folder::load(['=NAME' => $delivery['IBLOCK_ELEMENT_ID'], '=PARENT_ID' => DELIVERIES_DRIVE_FOLDER_ID]);

        if (empty($folder)) {
            $folder = Folder::add([
                'NAME'       => $delivery['IBLOCK_ELEMENT_ID'],
                'STORAGE_ID' => COMMON_STORAGE_ID,
                'PARENT_ID'  => DELIVERIES_DRIVE_FOLDER_ID,
            ], new ErrorCollection());
        }

        if (empty($delivery['FILES_FOLDER_ID'])) {
            CIBlockElement::SetPropertyValuesEx(
                $delivery['IBLOCK_ELEMENT_ID'],
                DELIVERY_IBLOCK_ID,
                ['FILES_FOLDER_ID' => $folder->getId()]
            );
        }

        return $folder;
    }

    /**
     * @param  string|null  $fileIds
     * @param  Folder  $folder
     *
     * @throws ArgumentException
     * @throws NotImplementedException
     */
    private function handleFiles(?string $fileIds, Folder $folder): void
    {
        if (empty($fileIds)) {
            return;
        }

        $fileIds = explode(',', $fileIds);
        foreach ($fileIds as $fileId) {
            $file = AttachedObject::load(['ID' => $fileId])->getFile();
            if (empty($file)) {
                continue;
            }

            if ($file->getParentId() == $folder->getId()) {
                continue;
            }

            $file->moveToAnotherFolder($folder, BITRIX_NOTIFICATION_USER);
        }
    }

    /**
     * @param  array  $delivery
     */
    private function handleContainersQuantity(array $delivery): void
    {
        $containerTypes = array_map(
            fn($item) => json_decode($item, true),
            $delivery['CONTAINER_TYPE']
        );

        CIBlockElement::SetPropertyValuesEx(
            $delivery['IBLOCK_ELEMENT_ID'],
            DELIVERY_IBLOCK_ID,
            ['CONTAINER_QUANTITY' => array_column($containerTypes, 'count')]
        );
    }

    /**
     * @param  int  $deliveryId
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getDelivery(int $deliveryId): ?array
    {
        return DeliveryPropertyValuesTable::query()
            ->setSelect(['*', 'PURCHASE_ORDER_ID', 'ORDER_ID', 'CONTAINER_TYPE', 'CUSTOMS_DOCUMENT_FILE_ID'])
            ->where('IBLOCK_ELEMENT_ID', $deliveryId)
            ->fetch() ?: null;
    }
}
