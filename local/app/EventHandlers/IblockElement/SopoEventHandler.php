<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\MiningElement\ERP\PurchaseOrderExportAgent;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\DealTable as PurchaseOrder;
use Bitrix\Crm\ActivityTable;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\IO\Path;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\TaskTable as Task;
use CBitrixComponent;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\PaymentPropertyValuesTable;
use Models\SCM\PurchaseOrderProductRowPricesTable;
use Models\SCM\SopoPropertyValueTable;
use Models\SCM\SpecificationPropertyValuesTable;
use Services\Infrastructure\SCM\OrderTasksHandler;
use Services\Infrastructure\EntityChangesLoggingService;

class SopoEventHandler implements OnBeforeUpdateEventHandlerInterface, OnAfterUpdateEventHandlerInterface, OnAfterAddEventHandlerInterface, OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface
{
    private static $state;

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws NotImplementedException
     * @throws ObjectPropertyException
     * @throws SqlQueryException
     * @throws SystemException todo сделать оптимизацию, чтобы логика срабатывало если изменено соотв. поле
     */
    public function onAfterUpdate($element): void
    {
        # свойства не меняются => выходим
        if (empty($element['PROPERTY_VALUES'])) {
            return;
        }

        EntityChangesLoggingService::run('sopo', $element['ID']);

        $sopoChain = $this->getSopoChain($element['ID']);
        $currentSopo = $sopoChain[$element['ID']];
        $purchaseOrder = PurchaseOrder::getByPrimary($currentSopo['PROCUREMENT'], ['select' => ['ID', 'UF_END_BUYER']])->fetch();
        $endBuyerId = $this->getEndBuyerId($sopoChain);
        $supplierId = $this->getSupplierId($sopoChain);
        $companiesRef = $this->getCompanies(array_merge(array_column($sopoChain, 'BUYER'), array_column($sopoChain, 'SELLER')));
        $propertyValues = [];

        # запишем в ПО конечного покупателя на основе цепочки sopo
        if ($purchaseOrder['UF_ENDBUYER'] != $endBuyerId) {
            PurchaseOrder::update($purchaseOrder['ID'], ['UF_END_BUYER' => $endBuyerId, 'COMPANY_ID' => $supplierId]);
        }

        # sopo отцеплена от ПО => удаляем и выходим
        if (empty($currentSopo['PROCUREMENT'])) {
            CIBlockElement::Delete($currentSopo['ID']);
            return;
        }

        # запишем номер контракта, если пусто
        if (empty($currentSopo['CONTRACT'])) {
            $supplyContract = CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID'          => SUPPLY_CONTRACTS_IBLOCK_ID,
                    'PROPERTY_SELLER'    => $currentSopo['SELLER'],
                    'PROPERTY_BUYER'     => $currentSopo['BUYER'],
                    'PROPERTY_CURRENCY'  => $currentSopo['CURRENCY'],
                    '!PROPERTY_ACTIVE'   => false,
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'NAME', 'ACTIVE_FROM']
            )->Fetch();
            if (!empty($supplyContract)) {
                $currentSopo['CONTRACT'] = $supplyContract['NAME'];
                $propertyValues['CONTRACT'] = $supplyContract['NAME'];
                $propertyValues['CONTRACT_DATE'] = $supplyContract['ACTIVE_FROM'];
            }
        }

        # запишем номер спецификации, если пусто
        if (empty($currentSopo['SPECIFICATION_NUMBER']) && !empty($currentSopo['CONTRACT'])) {
            $lastSopo = SopoPropertyValueTable::getList([
                'select' => ['MAX_SPECIFICATION_NUMBER'],
                'filter' => [
                    'SELLER'   => $currentSopo['SELLER'],
                    'BUYER'    => $currentSopo['BUYER'],
                    'CURRENCY' => $currentSopo['CURRENCY'],
                    'CONTRACT' => $currentSopo['CONTRACT'],
                ],
                'runtime' => [
                    new ExpressionField('MAX_SPECIFICATION_NUMBER', 'MAX(%s)', 'SPECIFICATION_NUMBER'),
                ],
            ])->fetch();
            $lastSpecificationNumber = $lastSopo['MAX_SPECIFICATION_NUMBER'] ?? 0;
            $lastSpecificationNumber++;

            $currentSopo['SPECIFICATION_NUMBER'] = $lastSpecificationNumber;
            $propertyValues['SPECIFICATION_NUMBER'] = $lastSpecificationNumber;
        }

        # обработаем папку sopo
        $sopoFolder = $this->handleSopoFolder($currentSopo, $companiesRef);
        $propertyValues['DRIVE_FOLDER'] = $sopoFolder ? $sopoFolder->getId() : 0;

        # переместим файлы инвойсов в папку сопо
        $proformaInvoiceFile = $this->getAttachedFile($currentSopo['PROFORMA_INVOICE_FILE_ID'], $currentSopo['ID']);
        if ($sopoFolder && $proformaInvoiceFile && $proformaInvoiceFile->getParentId() != $propertyValues['DRIVE_FOLDER']) {
            $proformaInvoiceFile->moveToAnotherFolder($sopoFolder, BITRIX_NOTIFICATION_USER, true);
        }

        $finalInvoiceFile = $this->getAttachedFile($currentSopo['FINAL_INVOICE_FILE_ID'], $currentSopo['ID']);
        if ($sopoFolder && $finalInvoiceFile && $finalInvoiceFile->getParentId() != $propertyValues['DRIVE_FOLDER']) {
            $finalInvoiceFile->moveToAnotherFolder($sopoFolder, BITRIX_NOTIFICATION_USER, true);
        }

        # запишем сумму
        $productRows = PurchaseOrderProductRowPricesTable::getList([
            'select' => ['PRICE', 'QUANTITY' => 'ROW.QUANTITY'],
            'filter' => ['CONTRACT_ID' => $element['ID']]
        ])->fetchAll();
        $propertyValues['AMOUNT'] = array_reduce($productRows, fn ($sum, $row) => $sum + (float) $row['PRICE'] * $row['QUANTITY']);

        if (!empty($propertyValues)) {
            $this->setPropertyValues($currentSopo['ID'], $propertyValues);
        }

        # запишем в платежи invoice number контракта
        $payments = $this->getPayments($currentSopo['PROCUREMENT'], $currentSopo['SELLER']);
        foreach ($payments as $payment) {
            if (!empty($currentSopo['INVOICE_NUMBER']['STRING']) && $payment['INVOICE_NUMBER'] != $currentSopo['INVOICE_NUMBER']['STRING']) {
                CIBlockElement::SetPropertyValuesEx(
                    $payment['ID'],
                    PAYMENTS_IBLOCK_ID,
                    ['INVOICE_NUMBER' => $currentSopo['INVOICE_NUMBER']['STRING']]
                );
            }
        }

        # найдём порядковый номер сопо в цепочке
        $sopoNumber = $this->getSopoNumber($sopoChain, $currentSopo['SELLER']);
        $this->setSopoName($currentSopo, $companiesRef, $sopoNumber);

        # если изменился invoice number и при этом имеем дело с сопо поставщика (1й в цепочке)
        if (isset(self::$state['onBeforeUpdate'])
            && array_key_exists($currentSopo['ID'], self::$state['onBeforeUpdate'])
            && $currentSopo['INVOICE_NUMBER']['STRING'] != self::$state['onBeforeUpdate'][$currentSopo['ID']]['INVOICE_NUMBER']
            && $sopoNumber === 1
        ) {
            $invoiceNumber = $currentSopo['INVOICE_NUMBER']['STRING'];
        }

        # запишем номер спецификации и контракт поставки в спецификацию
        $specification = SpecificationPropertyValuesTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', 'NUMBER', 'SUPPLY_CONTRACT_NUMBER'],
            'filter' => ['PURCHASE_ORDER_ID' => $currentSopo['PURCHASE_ORDER_ID'], 'SELLER_COMPANY_ID' => $currentSopo['SELLER']]
        ])->fetch();
        if ($specification && ($specification['NUMBER'] != $currentSopo['SPECIFICATION_NUMBER'] || $specification['SUPPLY_CONTRACT_NUMBER'] != $currentSopo['CONTRACT'])) {
            CIBlockElement::SetPropertyValuesEx(
                $specification['ID'],
                SPECIFICATION_IBLOCK_ID,
                [
                    'NUMBER'                 => $currentSopo['SPECIFICATION_NUMBER'],
                    'SUPPLY_CONTRACT_NUMBER' => $currentSopo['CONTRACT'],
                ]
            );
            (new CIBlockElement())->Update($specification['ID'], []);
        }

        $this->handlePurchaseOrderTasks($purchaseOrder['ID'], $sopoChain, $companiesRef, $invoiceNumber ?? null);

        PurchaseOrderExportAgent::scheduleRunOnce($purchaseOrder['ID']);
    }

    /**
     * @param $sopo
     *
     * @throws ArgumentException
     * @throws NotImplementedException
     * @throws ObjectPropertyException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function onAfterAdd($sopo): void
    {
        if (empty($sopo['ID'])) {
            return;
        }

        $this->onAfterUpdate($sopo);
    }

    /**
     * @param  array  $currentSopo
     * @param  array  $companiesRef
     * @param  int  $sopoNumber
     *
     * @throws SqlQueryException
     */
    private function setSopoName(array $currentSopo, array $companiesRef, int $sopoNumber): void
    {
        $sopoName = sprintf(
            "%d. %s -> %s",
            $sopoNumber,
            $companiesRef[$currentSopo['SELLER']]['UF_SHORT_NAME'] ?: $companiesRef[$currentSopo['SELLER']]['TITLE'] ?: 'N/A',
            $companiesRef[$currentSopo['BUYER']]['UF_SHORT_NAME'] ?: $companiesRef[$currentSopo['BUYER']]['TITLE'] ?: 'N/A'
        );

        Application::getConnection()->query("update b_iblock_element set NAME='{$sopoName}' where ID={$currentSopo['ID']}");
    }

    /**
     * @param  int  $sopoId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getSopoChain(int $sopoId): array
    {
        $dbResult = SopoPropertyValueTable::getList([
            'select' => [
                'IBLOCK_ELEMENT_ID',
                'PROCUREMENT',
                'SOPO_ID'                       => 'BY_PROCUREMENT.IBLOCK_ELEMENT_ID',
                'SOPO_SELLER'                   => 'BY_PROCUREMENT.SELLER',
                'SOPO_BUYER'                    => 'BY_PROCUREMENT.BUYER',
                'SOPO_DRIVE_FOLDER'             => 'BY_PROCUREMENT.DRIVE_FOLDER',
                'SOPO_SPECIFICATION_NUMBER'     => 'BY_PROCUREMENT.SPECIFICATION_NUMBER',
                'SOPO_INVOICE_NUMBER'           => 'BY_PROCUREMENT.INVOICE_NUMBER',
                'SOPO_CURRENCY'                 => 'BY_PROCUREMENT.CURRENCY',
                'SOPO_CONTRACT'                 => 'BY_PROCUREMENT.CONTRACT',
                'SOPO_PROFORMA_INVOICE_FILE_ID' => 'BY_PROCUREMENT.PROFORMA_INVOICE_FILE_ID',
                'SOPO_FINAL_INVOICE_FILE_ID'    => 'BY_PROCUREMENT.FINAL_INVOICE_FILE_ID',
            ],
            'filter'  => ['IBLOCK_ELEMENT_ID' => $sopoId],
            'runtime' => [
                new ReferenceField(
                    'BY_PROCUREMENT',
                    SopoPropertyValueTable::class,
                    ['=this.PROCUREMENT' => 'ref.PROCUREMENT'],
                    ['join_type' => 'LEFT']
                ),
            ],
        ]);
        while ($row = $dbResult->fetch()) {
            $sopoChain[$row['SOPO_ID']] = [
                'ID'                       => $row['SOPO_ID'],
                'PROCUREMENT'              => $row['PROCUREMENT'],
                'PURCHASE_ORDER_ID'        => $row['PROCUREMENT'],
                'BUYER'                    => $row['SOPO_BUYER'],
                'SELLER'                   => $row['SOPO_SELLER'],
                'DRIVE_FOLDER'             => $row['SOPO_DRIVE_FOLDER'],
                'SPECIFICATION_NUMBER'     => $row['SOPO_SPECIFICATION_NUMBER'],
                'INVOICE_NUMBER'           => $row['SOPO_INVOICE_NUMBER'],
                'CURRENCY'                 => $row['SOPO_CURRENCY'],
                'CONTRACT'                 => $row['SOPO_CONTRACT'],
                'PROFORMA_INVOICE_FILE_ID' => is_array($row['SOPO_PROFORMA_INVOICE_FILE_ID']) ? (int) current($row['SOPO_PROFORMA_INVOICE_FILE_ID']) : 0,
                'FINAL_INVOICE_FILE_ID'    => is_array($row['SOPO_FINAL_INVOICE_FILE_ID']) ? (int) current($row['SOPO_FINAL_INVOICE_FILE_ID']) : 0,
            ];
        }

        return $sopoChain ?? [];
    }

    /**
     * @param  array  $sopoChain
     * @param  int  $currentSopoSellerId

     *
     * @return int
     */
    private function getSopoNumber(array $sopoChain, int $currentSopoSellerId): int
    {
        foreach ($sopoChain as $sopo) {
            $sellersSopo[$sopo['SELLER']] = $sopo;
        }
        $sellerId = $this->getSupplierId($sellersSopo);
        $sopo = $sellersSopo[$sellerId];
        $n = 1;
        do {
            if ($sellerId == $currentSopoSellerId) {
                break;
            }

            $sellerId = $sopo['BUYER'];
            $n++;
        } while ($sopo = $sellersSopo[$sellerId]);

        return $n;
    }

    /**
     * @param  array  $sopoChain
     *
     * @return int
     */
    private function getEndBuyerId(array $sopoChain): int
    {
        # покупатель, который ничего не продаёт - конечный покупатель
        return
            current(
                array_diff(
                    array_column($sopoChain, 'BUYER'),
                    array_column($sopoChain, 'SELLER')
                )
            );
    }

    private function getSupplierId(array $sopoChain): int
    {
        # продавец, который ничего не покупает - исходный поставщик
        return
            current(
                array_diff(
                    array_column($sopoChain, 'SELLER'),
                    array_column($sopoChain, 'BUYER')
                )
            );
    }

    /**
     * @param  array  $sopo
     *
     * @param  array  $companiesRef
     *
     * @return Folder|null
     * @throws ArgumentException
     * @throws NotImplementedException
     */
    private function handleSopoFolder(array $sopo, array $companiesRef): ?Folder
    {
        $sellerCompanyFolderId = $companiesRef[$sopo['SELLER']]['UF_SPECIFICATIONS_FOLDER'] ?: $companiesRef[$sopo['SELLER']]['UF_DRIVE_FOLDER'] ?? 0;
        $sellerFolder = Folder::getById($sellerCompanyFolderId);
        if (empty($sellerFolder)) {
            return null;
        }

        $buyerCompany = $companiesRef[$sopo['BUYER']] ?? null;
        if (empty($buyerCompany)) {
            return null;
        }

        $specificationNumber = $sopo['SPECIFICATION_NUMBER'] ?: "PO-{$sopo['PROCUREMENT']}";
        $contractName = $sopo['CONTRACT'] ?: "NA";
        $contractFolderName = Path::replaceInvalidFilename("{$contractName} - {$specificationNumber} - {$buyerCompany['TITLE']}", function () {
            return '_';
        });
        if (!empty($sopo['DRIVE_FOLDER'])) {
            $sopoFolder = Folder::load(['ID' => $sopo['DRIVE_FOLDER']]);
        }

        if (empty($sopoFolder)) {
            $sopoFolder = $sellerFolder->addSubFolder(['NAME' => $contractFolderName]);
        }

        if (empty($sopoFolder)) {
            return null;
        }

        if ($sopoFolder->getName() !== $contractFolderName) {
            $sopoFolder->rename($contractFolderName);
        }

        if ($sopoFolder->getParentId() != $sellerFolder->getId()) {
            $sopoFolder->moveTo($sellerFolder, BITRIX_NOTIFICATION_USER);
        }

        return $sopoFolder;
    }

    /**
     * @param  int  $sopoId
     * @param  array  $propertyValues
     */
    private function setPropertyValues(int $sopoId, array $propertyValues = []): void
    {
        CIBlockElement::SetPropertyValuesEx(
            $sopoId,
            SOPO_IBLOCK_ID,
            $propertyValues
        );
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
        $dbResult = CompanyTable::getList([
            'select' => ['ID', 'TITLE', 'UF_SHORT_TITLE', 'UF_DRIVE_FOLDER', 'UF_SPECIFICATIONS_FOLDER'],
            'filter' => ['ID' => $companyIds],
        ]);
        while ($row = $dbResult->fetch()) {
            $companies[$row['ID']] = $row;
        }

        return $companies ?? [];
    }

    /**
     * @param  int  $purchaseOrderId
     * @param  int  $payeeCompanyId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPayments(int $purchaseOrderId, int $payeeCompanyId): array
    {
        return PaymentPropertyValuesTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', 'PURCHASE_ORDER_ID', 'PAYEE_COMPANY_ID'],
            'filter' => ['PURCHASE_ORDER_ID' => $purchaseOrderId, 'PAYEE_COMPANY_ID' => $payeeCompanyId]
        ])->fetchAll();
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @param  array  $sopoChain
     * @param  array  $companiesRef
     * @param  string|null  $invoiceNumber
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function handlePurchaseOrderTasks(int $purchaseOrderId, array $sopoChain, array $companiesRef, ?string $invoiceNumber = null): void
    {
        $tasks = $this->getOrderTasks($purchaseOrderId);
        if (empty($tasks)) {
            return;
        }

        $orderIds = array_filter(array_column($tasks, 'UF_PROC_ORDER_ID'));
        if (empty($orderIds)) {
            return;
        }

        foreach ($tasks as $task) {
            # если меняется invoice number - изменяем названия связанных задач
            if (isset($invoiceNumber)) {
                $taskFieldsValue['TITLE'] = OrderTasksHandler::getTaskTitle(['#INVOICE_NUMBER#' => $invoiceNumber], $task['TITLE']);

                # обновим названия задач в карточке сделки (crm.timeline)
                $activityFields = ActivityTable::getList([
                    'filter' => [
                        'ASSOCIATED_ENTITY_ID' => $task['ID'],
                        'COMPLETED'            => 'N'
                    ]
                ]);
                while ($activityFieldsValue = $activityFields->fetch()) {
                    ActivityTable::Update($activityFieldsValue['ID'], ['SUBJECT' => $taskFieldsValue['TITLE']]);
                }
            }

            if (empty($taskFieldsValue)) {
                continue;
            }

            Task::update($task['ID'], $taskFieldsValue);
        }
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @return array
     */
    private function getOrderTasks(int $purchaseOrderId): array
    {
        return Task::getList([
            'select' => ['ID', 'TITLE','DESCRIPTION', 'UF_PROC_ORDER_ID'],
            'filter' => ['UF_CRM_TASK' => 'D_'.$purchaseOrderId, 'ZOMBIE' => 'N']
        ])->fetchAll();
    }

    /**
     * @param  int  $sopoId
     *
     * @return string
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getSopoInvoiceNumber(int $sopoId): string
    {
        # записываем invoice number чтобы в onAfterSetPropertyValues проверить его на изменение
        $currentSopo = SopoPropertyValueTable::getList([
            'select' => ['INVOICE_NUMBER'],
            'filter' => ['IBLOCK_ELEMENT_ID' => $sopoId]
        ])->fetch();

        return $currentSopo['INVOICE_NUMBER']['STRING'] ?: '';
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
        self::$state['onBeforeUpdate'][$element['ID']]['INVOICE_NUMBER'] = $this->getSopoInvoiceNumber($element['ID']);

        return null;
    }

    /**
     * @param  int  $attachedFileId
     * @param  int  $sopoId
     *
     * @return array|null
     * @throws ArgumentException
     * @throws NotImplementedException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getAttachedFile(int $attachedFileId, int $sopoId): ?File
    {
        if (empty($attachedFileId)) {
            return null;
        }

        $attachedObject = AttachedObjectTable::getList([
            'select' => ['ID', 'OBJECT_ID'],
            'filter' => ['ID' => $attachedFileId, 'ENTITY_ID' => $sopoId]
        ])->fetch();
        if (empty($attachedObject)) {
            return null;
        }

        return File::load(['ID' => $attachedObject['OBJECT_ID']]);
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @throws ArgumentException
     * @throws NotImplementedException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void
    {
        if (empty($component->arResult['ELEMENT_ID'])) {
            return;
        }

        # редирект в папку сопо
        $redirectToDriveFolder = (bool) Application::getInstance()->getContext()->getRequest()->get('redirect_to_drive_folder');
        $folderId = $component->arResult['ELEMENT_PROPS'][SopoPropertyValueTable::getPropertyId('DRIVE_FOLDER')]['VALUE'] ?? null;
        if (empty($redirectToDriveFolder) || empty($folderId)) {
            return;
        }

        $folder = Folder::load(['ID' => $folderId]);
        if (empty($folder)) {
            return;
        }

        localRedirect(Driver::getInstance()->getUrlManager()->getPathFolderList($folder));
    }
}
