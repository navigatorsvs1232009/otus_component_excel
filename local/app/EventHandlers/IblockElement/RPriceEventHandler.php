<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\Me1C\RPriceExportAgent;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\RPRiceProductRowsPropertyValuesTable;
use Models\SCM\RPricePropertyValuesTable;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\IblockElementNameHandler;
use UserTypes\SCM\RPriceProductRows;

class RPriceEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface, OnAfterDeleteEventHandlerInterface, OnAfterUpdateEventHandlerInterface,
                                    OnAfterAddEventHandlerInterface, OnBeforeDeleteEventHandlerInterface, OnBeforeUpdateEventHandlerInterface
{
    private static array $state = [];

    /**
     * @param $element
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        RPriceExportAgent::scheduleRunOnce($element['ID']);
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

        $rprice = $this->getRPrice($elementId);
        RPriceProductRows::handleProductRows($rprice, RPriceProductRows::getRpriceProductRows($rprice['ID']), $propertyValues);
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
     * @param  int  $rpriceId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function deleteLinkedProductRows(int $rpriceId): void
    {
        foreach (RPriceProductRowsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID', 'XML_ID' => 'ELEMENT.XML_ID'],
            'filter' => ['XML_ID' => $rpriceId]
        ])->fetchAll() as $rpriceProductRow) {
            CIBlockElement::Delete($rpriceProductRow['IBLOCK_ELEMENT_ID']);
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
        }
    }

    /**
     * @param  int  $rpriceId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function deactivateLinkedProductRows(int $rpriceId): void
    {
        $element = new CIBlockElement();
        foreach (RPriceProductRowsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID'],
            'filter' => ['RPRICE_ID' => $rpriceId]
        ])->fetchAll() as $priceProductRow) {
            $element->Update($priceProductRow['IBLOCK_ELEMENT_ID'], ['ACTIVE' => 'N']);
        }
    }

    /**
     * @param  int  $rpriceId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected static function getRPrice(int $rpriceId): array
    {
        return RPricePropertyValuesTable::getList([
            'select' => [
                'ID'                    => 'IBLOCK_ELEMENT_ID',
                'STATUS_ID',
                'SHIPMENT_TERMS_ID',
                'DELIVERY_METHOD_VALUE' => 'DELIVERY_METHOD_LIST.VALUE',
                'ME_GUID',
                'DOCUMENT_NUMBER',
                'TYPE_ID',
                'OWNER',
                'CRM_SOURCE',
                'CUSTOMER'              => 'CLIENT',
                'REQUEST_NUMBER',
            ],
            'filter' => ['ID' => $rpriceId]
        ])->fetch() ?: [];
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
        EntityChangesLoggingService::run('rprice', $elementId, EntityChangesLoggingService::DELETE_ACTION, self::getRPrice($elementId));

        return null;
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
        $rprice = self::getRPrice($element['ID']);

        if ($rprice['STATUS_ID'] == RPRICE_SENT_TO_CRM_FULLY_STATUS_ID) {
            $GLOBALS['APPLICATION']->ThrowException('R-Price has status: Sent to CRM (fully),  update prohibited');
            return false;
        }

        self::$state['oBeforeUpdate'][$element['ID']] = $rprice;

        return null;
    }
}
