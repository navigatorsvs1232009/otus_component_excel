<?php

namespace EventHandlers\Components;

use CBitrixComponent;

class OrdersListBeforeIncludeComponentTemplateHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     */
    public function handle(CBitrixComponent $component): void
    {
        # скрываем ненужные кнопки
        $component->arResult['CAN_ADD_ELEMENT'] = false;
        $component->arResult['CAN_EDIT_SECTIONS'] = false;
    }
}
