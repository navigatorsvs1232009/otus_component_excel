<?php

namespace EventHandlers\Components;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CBitrixComponent;

class ListsListBeforeIncludeComponentDefaultTemplateEventHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function handle(CBitrixComponent $component): void
    {
        switch ($component->arParams['IBLOCK_ID']) {

            case RPRICE_PRODUCT_ROWS_IBLOCK_ID:
                (new RpriceByProductListBeforeIncludeComponentTemplateHandler())->handle($component);
                break;

            case PAYMENTS_IBLOCK_ID:
                (new PaymentsListBeforeIncludeComponentTemplateHandler())->handle($component);
                break;

            case DELIVERY_IBLOCK_ID:
                (new DeliveryListBeforeIncludeComponentTemplateHandler())->handle($component);
                break;

            case ORDERS_IBLOCK_ID:
                (new OrdersListBeforeIncludeComponentTemplateHandler())->handle($component);
                break;
        }
    }
}
