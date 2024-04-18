<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\Me1C\RfqExportAgent;
use Bitrix\Crm\CompanyTable;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\CompanySupplyAreaPropertyValuesTable;
use Models\SCM\RfqProductRowsPropertyValuesTable;
use Models\SCM\RfqPropertyValuesTable;
use Repositories\PricePerKiloRepository;
use Repositories\ProductMaterialsRef;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\IblockElementNameHandler;
use Throwable;
use UserTypes\SCM\RFQProductRows;

class RfqEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface, OnAfterDeleteEventHandlerInterface, OnAfterUpdateEventHandlerInterface,
                                 OnAfterAddEventHandlerInterface, OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface, OnBeforeUpdateEventHandlerInterface,
                                 OnBeforeAddEventHandlerInterface, OnBeforeDeleteEventHandlerInterface
{
    /**
     * @param $element
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        CIBlockElement::SetPropertyValuesEx(
            $element['ID'],
            RFQ_IBLOCK_ID,
            ['STATUS_ID' => RPRICE_STATUS_NEW_ID]
        );
    }

    /**
     * @param $elementId
     * @param $propertyValues
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterSetPropertyValues($elementId, $propertyValues): void
    {
        IblockElementNameHandler::handle($elementId);

        $rfq = $this->getRfq($elementId);
        $pricesPerKiloRef = $rfq['SUPPLIER_ID'] ? PricePerKiloRepository::getSupplierPricesPerKilo($rfq['SUPPLIER_ID'], $rfq['INCOTERMS_ID'], $rfq['INCOTERMS_PLACE_ID']) : [];
        RFQProductRows::handleProductRows(
            $rfq,
            RFQProductRows::getProductRows(['RFQ_ID' => $rfq['ID']], $pricesPerKiloRef, ProductMaterialsRef::all('NAME')),
            $propertyValues
        );

        if (empty($propertyValues['importing'])
            && ($rfq['EXPORT_TO_1C_ME_TYPE_XML_ID'] === 'yes' || $rfq['EXPORT_TO_1C_ME_TYPE_XML_ID'] === 'custom')
        ) {
            RfqExportAgent::scheduleRunOnce($rfq['ID']);
        }

        $this->moveAttachmentsToSupplierPriceIndicationFolder($elementId, $propertyValues);

        EntityChangesLoggingService::run('rfq', $elementId, EntityChangesLoggingService::UPDATE_ACTION);
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
        $this->deleteLinkedProductRows($element['ID']);
    }

    /**
     * @param  int  $rfqId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function deleteLinkedProductRows(int $rfqId): void
    {
        foreach (RfqProductRowsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID', 'XML_ID' => 'ELEMENT.XML_ID'],
            'filter' => ['XML_ID' => $rfqId]
        ])->fetchAll() as $rfqProductRow) {
            CIBlockElement::Delete($rfqProductRow['IBLOCK_ELEMENT_ID']);
        }
    }

    /**
     * @param $elementId
     * @param $propertyValues
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws NotImplementedException
     */
    protected function moveAttachmentsToSupplierPriceIndicationFolder($elementId, $propertyValues): void
    {
        $attachmentsPropertyId = RfqPropertyValuesTable::getPropertyId('ATTACHMENTS');
        $attachments = array_filter(current($propertyValues[$attachmentsPropertyId] ?? [])['VALUE'] ?? $propertyValues['ATTACHMENTS'] ?? []);

        $supplierIdPropertyId = RfqPropertyValuesTable::getPropertyId('SUPPLIER_ID');
        $supplierId = current($propertyValues[$supplierIdPropertyId] ?? [])['VALUE'] ?? $propertyValues['SUPPLIER_ID'] ?? null;
        if (empty($supplierId)) {
            return;
        }

        $supplier = CompanyTable::getList([
            'select' => ['UF_RQ_PRICE_FOLDER'],
            'filter' => ['ID' => $supplierId]
        ])->fetch();
        if (empty($supplier['UF_RQ_PRICE_FOLDER'])) {
            return;
        }

        $folder = Folder::load(['ID' => $supplier['UF_RQ_PRICE_FOLDER']]);
        if (empty($folder)) {
            return;
        }

        foreach ($attachments as $fileId) {
            try {
                if ($fileId[0] === 'n') {
                    $file = File::load(['ID' => substr($fileId,1)]);
                } else {
                    $file = AttachedObject::load(['ID' => $fileId])->getFile();
                }
            } catch (Throwable) {
                continue;
            }

            if (empty($file)) {
                continue;
            }

            if ($file->getParentId() != $supplier['UF_RQ_PRICE_FOLDER']) {
                $file->moveToAnotherFolder($folder, BITRIX_NOTIFICATION_USER);
            }
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
        if (isset($element['ACTIVE']) && $element['ACTIVE'] == 'N') {
            $this->deactivateLinkedProductRows($element['ID']);
            RfqExportAgent::scheduleRunOnce($element['ID']);
        }
    }

    /**
     * @param  int  $rfqId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function deactivateLinkedProductRows(int $rfqId): void
    {
        $element = new CIBlockElement();
        foreach (RfqProductRowsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID'],
            'filter' => ['RFQ_ID' => $rfqId]
        ])->fetchAll() as $rfqProductRow) {
            $element->Update($rfqProductRow['IBLOCK_ELEMENT_ID'], ['ACTIVE' => 'N']);
        }
    }

    /**
     * @param  int  $rfqId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getRfq(int $rfqId): array
    {
        return RfqPropertyValuesTable::getList([
            'select' => [
                'ID' => 'IBLOCK_ELEMENT_ID',
                'CURRENCY',
                'SUPPLIER_ID',
                'EXPORT_TO_1C_ME_TYPE_XML_ID' => 'EXPORT_TO_1C_ME_TYPE.XML_ID',
                'STATUS_ID',
                'ME_GUID',
                'INCOTERMS_PLACE_ID',
                'INCOTERMS_ID',
            ],
            'filter' => ['ID' => $rfqId]
        ])->fetch() ?: [];
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
        $rfq = $this->getRfq($component->arResult['ELEMENT_ID']);

        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->get('external_context') === 'creatingElementFromCrm') {
            list(, $supplierCompanyId) = explode('_', $request->get('defaultValue') ?? '');
        }

        $isCopy = $request->get('copy_id') > 0;

        # обработаем поля формы
        foreach ($component->arResult['FIELDS'] as &$field) {

            # rfq уже выгружен в 1с => запрещаем снимать галку экспорта
            if ($rfq
                &&$field['CODE'] === 'EXPORT_TO_1C_ME_TYPE_ENUM_ID'
                && !empty($rfq['ME_GUID'])
            ) {
                $field['SETTINGS']['EDIT_READ_ONLY_FIELD'] = 'Y';
            }

            # очищаем guid при копировании
            if ($isCopy && $field['CODE'] == 'ME_GUID') {
                $component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUE'] = null;
                $component->arResult['ELEMENT_PROPS'][$field['ID']]['FULL_VALUES'] = null;
                $component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUES_LIST'] = null;
            }

            $field['EXTERNAL_CONTEXT']['SUPPLIER_COMPANY_ID'] = $supplierCompanyId ?? null;
        }

        Extension::load('element.lists.rfq.element_edit');
    }

    /**
     * @param $element
     *
     * @return false|void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeUpdate(&$element): ?bool
    {
        if (empty($element['PROPERTY_VALUES'])) {
            return null;
        }

        $supplierCompanyId = (int) current($element['PROPERTY_VALUES'][RfqPropertyValuesTable::getPropertyId('SUPPLIER_ID')] ?: [])['VALUE'];
        if (empty($supplierCompanyId) || $supplierCompanyId == NA_COMPANY_ID) {
            return null;
        }

        # проверим, указано ли место отгрузки
        $incotermsPlaceId = (int) current($element['PROPERTY_VALUES'][RfqPropertyValuesTable::getPropertyId('INCOTERMS_PLACE_ID')] ?: [])['VALUE'];
        if (empty($incotermsPlaceId)) {
            $GLOBALS['APPLICATION']->ThrowException('Incoterms place is not set');
            return false;
        }

        # проверим, что указанное место входит в перечень зарегистрированных для данного поставщика
        $supplierCompanyIncotermsPlaceIds = array_column(CompanySupplyAreaPropertyValuesTable::getList([
            'select' => ['INCOTERMS_PLACE_ID'],
            'filter' => ['COMPANY_ID' => $supplierCompanyId]
        ])->fetchAll(), 'INCOTERMS_PLACE_ID');
        if (!in_array($incotermsPlaceId, $supplierCompanyIncotermsPlaceIds)) {
            $GLOBALS['APPLICATION']->ThrowException('Incoterms place is not suitable for this supplier');
            return false;
        }

        return null;
    }

    /**
     * @param $element
     *
     * @return false|void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeAdd(&$element): ?bool
    {
        return $this->onBeforeUpdate($element);
    }

    /**
     * @param  int  $elementId
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeDelete(int $elementId): ?bool
    {
        $rfq = $this->getRfq($elementId);
        if (!empty($rfq['ME_GUID'])) {
            $GLOBALS['APPLICATION']->ThrowException('Unable to delete RFQ exported to ERP');
            return false;
        }

        return null;
    }
}
