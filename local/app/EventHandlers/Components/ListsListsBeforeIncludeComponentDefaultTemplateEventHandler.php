<?php

namespace EventHandlers\Components;

use CBitrixComponent;

class ListsListsBeforeIncludeComponentDefaultTemplateEventHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     */
    public function handle(CBitrixComponent $component): void
    {
        $component->arResult['ITEMS'][] = [
            'NAME'     => 'Payment date adjustment',
            'LIST_URL' => '/crm/payment_date_adjustment/',
            'IMAGE'    => '<img src="/bitrix/images/lists/default.png" width="36" height="30" border="0" alt="" />',
        ];
    }
}