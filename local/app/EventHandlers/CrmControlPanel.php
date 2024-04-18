<?php

namespace EventHandlers;

use Bitrix\Main\SystemException;
use Repositories\UsersRepository;

abstract class CrmControlPanel
{
    /**
     * @param  array  $items
     *
     * @throws SystemException
     */
    public static function onAfterBuild(array &$items): void
    {
        foreach ($items as &$item) {
            switch ($item['ID']) {
                case  'DEAL':
                    $item['NAME'] = $item['TITLE'] = 'Purchase order';
                    $pureItems[] = self::createProductDemandsItem();
                    break;

                case 'LEAD':
                    $item['NAME'] = $item['TITLE'] = 'Demand';
                    $item['COUNTER'] = 0;
                    break;

                case 'CONTACT':
                case 'PRODUCT':
                case 'SETTINGS':
                    $item['IS_DISABLED'] = true;
                    break;

                case 'COMPANY':
                    $pureItems[] = self::createOrdersItem();
                    $pureItems[] = self::createPaymentsItem();
                    $pureItems[] = self::createContractsItem();
                    $pureItems[] = self::createRFQItem();
                    $pureItems[] = self::createPurchaseOrderDocumentsItem();
                    $pureItems[] = self::createPaymentDateAdjustmentItem();

                    if ($GLOBALS['USER']->IsAdmin() || in_array(ME_PRODUCTION_WORKGROUP_ID, UsersRepository::getUserWorkgroupIds($GLOBALS['USER']->GetID()))) {
                        $pureItems[] = self::createProductionApprovalItem();
                    }

                    if ($GLOBALS['USER']->IsAdmin() || in_array(ME_PRODUCTION_WORKGROUP_ID, UsersRepository::getUserWorkgroupIds($GLOBALS['USER']->GetID()))) {
                        $pureItems[] = self::createDesignWorkItem();
                    }

                    continue 2;

                case 'RECYCLE_BIN':
                    break;

                # убираем не нужные
                default:
                    continue 2;
            }

            $pureItems[] = $item;
        }

        $pureItems[] = self::createPurchasedProductDemandsItem();

        $items = $pureItems ?? [];
    }

    /**
     * @return array|string[]
     */
    private static function createDesignWorkItem(): array
    {
        return [
            'ID'      => 'DESIGN_WORK',
            'MENU_ID' => 'menu_scm_design_work',
            'NAME'    => 'Design work',
            'TITLE'   => 'Design work',
            'URL'     => '/crm/design_work/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return array
     */
    private static function createProductDemandsItem(): array
    {
        return [
            'ID'      => 'PRODUCT_DEMANDS',
            'MENU_ID' => 'menu_scm_product_demands',
            'NAME'    => 'Demand by product',
            'TITLE'   => 'Demand by product',
            'URL'     => '/crm/product_demands/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return array
     */
    private static function createOrdersItem(): array
    {
        return [
            'ID'      => 'PROCUREMENT_ORDERS',
            'MENU_ID' => 'menu_scm_orders',
            'NAME'    => 'Order',
            'TITLE'   => 'Order',
            'URL'     => '/crm/procurement_orders/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return array
     */
    private static function createPaymentsItem(): array
    {
        return [
            'ID'      => 'PAYMENTS',
            'MENU_ID' => 'menu_scm_payments',
            'NAME'    => 'Payments',
            'TITLE'   => 'Payments',
            'URL'     => '/crm/payments/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return array
     */
    private static function createContractsItem(): array
    {
        return [
            'ID'      => 'PROCUREMENT_CONTRACTS',
            'MENU_ID' => 'menu_scm_contracts',
            'NAME'    => 'SO/PO chain',
            'TITLE'   => 'SO/PO chain',
            'URL'     => '/crm/procurement_contracts/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return array
     */
    private static function createRFQItem(): array
    {
        return [
            'ID'      => 'RFQ',
            'MENU_ID' => 'menu_scm_rfq',
            'NAME'    => 'RFQ',
            'TITLE'   => 'RFQ',
            'URL'     => '/crm/rfq/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return string[]
     */
    private static function createProductionApprovalItem(): array
    {
        return [
            'ID'      => 'PRODUCTION_APPROVAL',
            'MENU_ID' => 'menu_scm_production_approval',
            'NAME'    => 'Production approval',
            'TITLE'   => 'Production approval',
            'URL'     => '/crm/production_approval/',
            'ICON'    => 'deal',
        ];
    }

    /**
     * @return string[]
     */
    private static function createPurchasedProductDemandsItem(): array
    {
        return [
            'ID'          => 'PURCHASED_PRODUCT_DEMANDS',
            'MENU_ID'     => 'menu_scm_purchased_product_demands',
            'NAME'        => 'Purchased products',
            'TITLE'       => 'Purchased products',
            'URL'         => '/crm/purchased_product_demands/',
            'ICON'        => 'deal',
            'IS_DISABLED' => true,
        ];
    }

    /**
     * @return array
     */
    private static function createPurchaseOrderDocumentsItem(): array
    {
        return [
            'ID'          => 'PURCHASE_ORDER_DOCUMENTS',
            'MENU_ID'     => 'menu_scm_purchase_order_documents',
            'NAME'        => 'Purchase order documents',
            'TITLE'       => 'Purchase order documents',
            'URL'         => '/crm/purchase_order_documents/',
            'ICON'        => 'deal',
            'IS_DISABLED' => true,
        ];
    }

    /**
     * @return array
     */
    private static function createPaymentDateAdjustmentItem(): array
    {
        return [
            'ID'          => 'PAYMENT_DATE_ADJUSTMENT',
            'MENU_ID'     => 'menu_scm_payment_date_adjustment',
            'NAME'        => 'Payment date adjustment',
            'TITLE'       => 'Payment date adjustment',
            'URL'         => '/crm/payment_date_adjustment/',
            'ICON'        => 'deal',
            'IS_DISABLED' => true,
        ];
    }
}
