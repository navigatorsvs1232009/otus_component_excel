<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\PaymentTermsDataPropertyValuesTable;
use Models\SCM\PaymentTermsHistoryTable;
use Models\SCM\PaymentTermsPropertyValuesTable;

class PaymentTermsEventHandler implements OnAfterAddEventHandlerInterface, OnAfterUpdateEventHandlerInterface, OnBeforeDeleteEventHandlerInterface, OnBeforeUpdateEventHandlerInterface
{
    private array $state = [];
    private int $userId;

    /**
     * PaymentTermsEventHandler constructor.
     */
    public function __construct()
    {
        $this->userId = $GLOBALS['USER']->GetId();
    }

    /**
     * @param $element
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeUpdate(&$element): bool
    {
        $this->state['onBeforeUpdate'][$element['ID']] = $this->getPaymentTermsItem($element['ID']);

        return true;
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function onAfterUpdate($element): void
    {
        $paymentTermsItem = PaymentTermsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID', 'OPERATING_NAME' => 'ELEMENT.PREVIEW_TEXT', 'SUPPLIER_COMPANY_ID', 'BUYER_COMPANY_ID', 'PAYMENT_TERMS_DATA'],
            'filter' => ['=IBLOCK_ELEMENT_ID' => $element['ID']]
        ])->fetch();

        $paymentTermsDataItems = PaymentTermsDataPropertyValuesTable::getList([
            'filter' => ['PAYMENT_TERMS_ID' => $element['ID']]
        ])->fetchAll();

        $this->handlePaymentTermsDataItems($paymentTermsItem, $paymentTermsDataItems);
        $this->handlePaymentTermsItemName($paymentTermsItem, $paymentTermsDataItems);

        #Если добавляется новая запись в список Payment terms, то запишем пустой массив в state[onBeforeUpdate]
        $paymentTermsBeforeUpdate = $this->state['onBeforeUpdate'][$element['ID']] ?: [];
        $paymentTermsAfterUpdate = $this->getPaymentTermsItem($element['ID']);

        $this->handlePaymentTermsChanges($paymentTermsBeforeUpdate, $paymentTermsAfterUpdate);
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        $this->onAfterUpdate($element);
    }

    /**
     * @param $elementId
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeDelete($elementId): bool
    {
        $paymentTermsDataItems = PaymentTermsDataPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID'],
            'filter' => ['PAYMENT_TERMS_ID' => $elementId]
        ])->fetchAll();

        foreach ($paymentTermsDataItems as $paymentTermsDataItem) {
            CIBlockElement::Delete($paymentTermsDataItem['IBLOCK_ELEMENT_ID']);
        }

        return true;
    }

    /**
     * @param  array  $paymentTermsItem
     * @param  array  $paymentTermsDataItems
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SqlQueryException
     * @throws SystemException
     */
    private function handlePaymentTermsItemName(array $paymentTermsItem, array $paymentTermsDataItems): void
    {
        if (empty($paymentTermsItem['OPERATING_NAME'])) {
            $typeOptions = PaymentTermsDataPropertyValuesTable::getEnumPropertyOptions('TYPE');
            $pre = $post = 0;

            foreach ($paymentTermsDataItems as $paymentTermsDataItem) {
                if ($typeOptions[$paymentTermsDataItem['TYPE']]['VALUE'] === 'Pre') {
                    $pre += $paymentTermsDataItem['PERCENT_TO_PAY'];
                }

                if ($typeOptions[$paymentTermsDataItem['TYPE']]['VALUE'] === 'Post') {
                    $post += $paymentTermsDataItem['PERCENT_TO_PAY'];
                }
            }

            $name = "{$pre}% pre payment, {$post}% post payment";
        } else {
            $name = $paymentTermsItem['OPERATING_NAME'];
        }


        Application::getConnection()->query(
            sprintf("update b_iblock_element set NAME='%s' where ID=%d",
                    $name,
                    $paymentTermsItem['IBLOCK_ELEMENT_ID']
            )
        );
    }

    /**
     * @param  array  $paymentTermsItem
     * @param  array  $paymentTermsDataItems
     */
    private function handlePaymentTermsDataItems(array $paymentTermsItem, array &$paymentTermsDataItems): void
    {
        $paymentTermsData = json_decode($paymentTermsItem['PAYMENT_TERMS_DATA'], true) ?: [];

        # обновим существующие
        foreach ($paymentTermsDataItems as $i => &$paymentTermsDataItem) {
            $paymentTermsDatum = $paymentTermsData[$paymentTermsDataItem['IBLOCK_ELEMENT_ID']];

            if (empty($paymentTermsDatum)) {
                CIBlockElement::Delete($paymentTermsDataItem['IBLOCK_ELEMENT_ID']);
                unset($paymentTermsDataItems[$i]);
                continue;
            }

            $paymentTermsDataItem = [
                'TYPE'                    => $paymentTermsDatum['type'],
                'ORDER_STAGE_ID'          => $paymentTermsDatum['orderStageId'],
                'PERCENT_TO_PAY'          => $paymentTermsDatum['percentToPay'],
                'POST_PERIOD_DAYS'        => $paymentTermsDatum['postPeriodDays'],
                'INCLUDED_PRODUCT_KEY_ID' => $paymentTermsDatum['includedProductKeyIds'] ?? null,
                'EXCLUDED_PRODUCT_KEY_ID' => $paymentTermsDatum['excludedProductKeyIds'] ?? null,
                'SUPPLIER_COMPANY_ID'     => $paymentTermsItem['SUPPLIER_COMPANY_ID'],
                'BUYER_COMPANY_ID'        => $paymentTermsItem['BUYER_COMPANY_ID'],
            ] + $paymentTermsDataItem;

            CIBlockElement::SetPropertyValuesEx(
                $paymentTermsDataItem['IBLOCK_ELEMENT_ID'],
                PAYMENT_TERMS_DATA_IBLOCK_ID,
                $paymentTermsDataItem
            );
            unset($paymentTermsData[$paymentTermsDataItem['IBLOCK_ELEMENT_ID']]);
        }
        unset($paymentTermsDataItem);

        # добавим новые
        $iblockElement = new CIBlockElement();
        foreach ($paymentTermsData as $paymentTermsDatum) {

            $paymentTermsDataItem = [
                'PAYMENT_TERMS_ID'        => $paymentTermsItem['IBLOCK_ELEMENT_ID'],
                'TYPE'                    => $paymentTermsDatum['type'],
                'ORDER_STAGE_ID'          => $paymentTermsDatum['orderStageId'],
                'PERCENT_TO_PAY'          => $paymentTermsDatum['percentToPay'],
                'POST_PERIOD_DAYS'        => $paymentTermsDatum['postPeriodDays'],
                'INCLUDED_PRODUCT_KEY_ID' => $paymentTermsDatum['includedProductKeyIds'] ?? null,
                'EXCLUDED_PRODUCT_KEY_ID' => $paymentTermsDatum['excludedProductKeyIds'] ?? null,
                'SUPPLIER_COMPANY_ID'     => $paymentTermsItem['SUPPLIER_COMPANY_ID'],
                'BUYER_COMPANY_ID'        => $paymentTermsItem['BUYER_COMPANY_ID'],
            ];
            $paymentTermsDataItem['IBLOCK_ELEMENT_ID'] = $iblockElement->Add([
                'NAME'            => '-',
                'IBLOCK_ID'       => PAYMENT_TERMS_DATA_IBLOCK_ID,
                'PROPERTY_VALUES' => $paymentTermsDataItem,
            ]);

            $paymentTermsDataItems[] = $paymentTermsDataItem;
        }
    }

    /**
     * @param $paymentTermsId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPaymentTermsItem ($paymentTermsId): array {
        $paymentTermsItem = PaymentTermsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID', 'OPERATING_NAME' => 'ELEMENT.PREVIEW_TEXT', 'SUPPLIER_COMPANY_ID', 'BUYER_COMPANY_ID'],
            'filter' => ['=IBLOCK_ELEMENT_ID' => $paymentTermsId],
        ])->fetch();

        $paymentTermsDataItems = PaymentTermsDataPropertyValuesTable::getList([
            'filter' => ['PAYMENT_TERMS_ID' => $paymentTermsId]
        ])->fetchAll() ?: [];

        if (!empty($paymentTermsDataItems)) {
            foreach ($paymentTermsDataItems as $paymentTermsDataItem) {
                $paymentTermsItem['PAYMENT_TERMS_DATA'][$paymentTermsDataItem['IBLOCK_ELEMENT_ID']] = $paymentTermsDataItem;
            }
        } else {
            $paymentTermsItem['PAYMENT_TERMS_DATA'] = [];
        }

        return $paymentTermsItem;
    }

    /**
     * @param $stateBeforeUpdate
     * @param $stateAfterUpdate
     */
    private function handlePaymentTermsChanges ($stateBeforeUpdate, $stateAfterUpdate): void
    {
        if ($stateBeforeUpdate === $stateAfterUpdate) {
            return;
        }

        $data = [
            'PAYMENT_TERMS_BEFORE' => $stateBeforeUpdate,
            'PAYMENT_TERMS_AFTER'  => $stateAfterUpdate,
        ];

        PaymentTermsHistoryTable::add([
            'PAYMENT_TERMS_ID'    => $stateAfterUpdate['IBLOCK_ELEMENT_ID'],
            'MODIFIED_BY_ID'      => $this->userId,
            'DATA'                => json_encode($data),
        ]);
    }
}
