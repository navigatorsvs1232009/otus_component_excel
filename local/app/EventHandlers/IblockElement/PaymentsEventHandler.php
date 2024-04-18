<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\Me1C\PaymentExportAgent;
use Bitrix\Crm\DealTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CBitrixComponent;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\PaymentPropertyValuesTable;
use Models\SCM\SopoPropertyValueTable;
use Repositories\PaymentsRepository;
use Repositories\UserGroupsRepository;
use Repositories\UsersRepository;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\IblockElementNameHandler;

class PaymentsEventHandler implements OnAfterAddEventHandlerInterface, OnAfterUpdateEventHandlerInterface, OnBeforeUpdateEventHandlerInterface, OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface
{
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

        $payment = $this->getPayment($element['ID']);
        if (empty($payment)) {
            return;
        }

        $this->handleProperties($payment);

        if ($payment['APPROVED_BY_FINANCIAL_MANAGER'] && $payment['PAYER_COMPANY_ID'] == ME_COMPANY_ID) {
            PaymentExportAgent::scheduleRunOnce($element['ID']);
        }

        IblockElementNameHandler::handle($element['ID']);
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
        EntityChangesLoggingService::run('payment', $element['ID']);

        $this->onAfterAdd($element);

        if (empty($element['PROPERTY_VALUES'])) {
            return;
        }

        $this->handleBankTransaction($element['PROPERTY_VALUES']);
    }

    /**
     * @param $element
     *
     * @return bool|null
     */
    public function onBeforeUpdate(&$element): ?bool
    {
        return null;
    }

    /**
     * @param  int  $paymentId
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPayment(int $paymentId): ?array
    {
        return PaymentPropertyValuesTable::query()
            ->setSelect([
                'ID' => 'IBLOCK_ELEMENT_ID',
                'STATUS',
                'PAYER_COMPANY_ID',
                'PAYEE_COMPANY_ID',
                'PURCHASE_ORDER_ID',
                'INVOICE_NUMBER',
                'APPROVED',
                'APPROVED_BY_FINANCIAL_MANAGER',
                'AMOUNT',
                'PAID_AMOUNT',
                'ORDER_STAGE_NAME' => 'ORDER_REF.STAGE_REF.NAME',
            ])
            ->where('ID', $paymentId)
            ->fetch() ?: null;
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
    private function getPayeeSopo(int $purchaseOrderId, int $payeeCompanyId): array
    {
        return SopoPropertyValueTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', 'INVOICE_NUMBER'],
            'filter' => ['PURCHASE_ORDER_ID' => $purchaseOrderId, 'SELLER_COMPANY_ID' => $payeeCompanyId],
            'limit'  => 1,
        ])->fetch() ?: [];
    }

    /**
     * @param  array  $payment
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function handleProperties(array $payment): void
    {
        $payeeSopo = $this->getPayeeSopo($payment['PURCHASE_ORDER_ID'], $payment['PAYEE_COMPANY_ID']);

        # обновим номер инвойса из сопо
        if (!empty($payeeSopo['INVOICE_NUMBER']['STRING'])
            && $payeeSopo['INVOICE_NUMBER']['STRING'] != $payment['INVOICE_NUMBER']) {

            $propertyValues['INVOICE_NUMBER'] = $payeeSopo['INVOICE_NUMBER']['STRING'];
        }

        $paymentStatus = $this->getPaymentStatus($payment);
        if ($payment['STATUS'] !== $paymentStatus) {
            $propertyValues['STATUS'] = $paymentStatus;
        }

        $purchaseOrder = $this->getPurchaseOrder($payment['PURCHASE_ORDER_ID']);
        if (!empty($purchaseOrder)) {
            $propertyValues['PURCHASE_ORDER_RESPONSIBLE_ID'] = $purchaseOrder['ASSIGNED_BY_ID'];
        }

        # запишем стадию ордера в платеж
        $propertyValues['ORDER_STAGE'] = $payment['ORDER_STAGE_NAME'];

        if (!empty($propertyValues)) {
            CIBlockElement::SetPropertyValuesEx(
                $payment['ID'],
                PAYMENTS_IBLOCK_ID,
                $propertyValues
            );
        }
    }

    /**
     * @param  array  $payment
     *
     * @return int|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPaymentStatus(array $payment): ?int
    {
        $paymentStatusesRef = PaymentsRepository::getStatusesRef('XML_ID');

        if (empty($payment['STATUS'])) {
            $paymentStatus = $paymentStatusesRef['IN_PROGRESS']['ID'];
        } else {
            $paymentStatus = $payment['STATUS'];
        }

        return $paymentStatus;
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void
    {
        $currentUser = UsersRepository::getCurrentUser();
        $hasFullApprovalPermission = $currentUser->IsAdmin() || in_array(
            UserGroupsRepository::getFinancialManagersGroupId(),
            $currentUser->GetUserGroupArray()
        );

        foreach ($component->arResult['FIELDS'] as &$field) {
            if (!$hasFullApprovalPermission && $field['CODE'] === 'APPROVED_BY_FINANCIAL_MANAGER') {
                $field['SETTINGS']['EDIT_READ_ONLY_FIELD'] = 'Y';
            }
        }
    }

    /**
     * @param array $propertyValues
     *
     * @return int|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function fetchBankTransactionIdValue(array $propertyValues): ?int
    {
        $bankTransactionPropertyId = PaymentPropertyValuesTable::getPropertyId('BANK_TRANSACTION_ID');

        return $propertyValues[$bankTransactionPropertyId];
    }

    /**
     * @param int $bankTransactionId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getBankTransactionPaymentSums(int $bankTransactionId): array
    {
        return PaymentPropertyValuesTable::getList([
            'select'  => ['AMOUNT_SUM', 'PAID_AMOUNT_SUM', 'PAID_AMOUNT_FACT_SUM'],
            'filter'  => ['BANK_TRANSACTION_ID' => $bankTransactionId],
            'runtime' => [
                new ExpressionField('AMOUNT_SUM', 'sum(REPLACE(%s, ",", "."))', 'AMOUNT'),
                new ExpressionField('PAID_AMOUNT_SUM', 'sum(REPLACE(%s, ",", "."))', 'PAID_AMOUNT'),
                new ExpressionField('PAID_AMOUNT_FACT_SUM', 'sum(REPLACE(%s, ",", "."))', 'PAID_AMOUNT_FACT'),
            ],
        ])->fetch();
    }

    /**
     * @param array $paymentPropertyValues
     *
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function handleBankTransaction(array $paymentPropertyValues): void
    {
        $bankTransactionId = $this->fetchBankTransactionIdValue($paymentPropertyValues);
        if (empty($bankTransactionId)) {
            return;
        }

        $bankTransactionPaymentSums = $this->getBankTransactionPaymentSums($bankTransactionId);
        CIBlockElement::SetPropertyValuesEx(
            $bankTransactionId,
            BANK_TRANSACTIONS_IBLOCK_ID,
            [
                'AMOUNT' => $bankTransactionPaymentSums['AMOUNT_SUM'],
                'AMOUNT_PAID' => $bankTransactionPaymentSums['PAID_AMOUNT_SUM'],
                'AMOUNT_PAID_FACT' => $bankTransactionPaymentSums['PAID_AMOUNT_FACT_SUM'],
            ],
        );
    }

    /**
     * @param int $purchaseOrderId
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPurchaseOrder(int $purchaseOrderId): ?array
    {
        return DealTable::getList([
            'select' => ['ASSIGNED_BY_ID'],
            'filter' => ['ID' => $purchaseOrderId],
        ])->fetch() ?: null;
    }
}
