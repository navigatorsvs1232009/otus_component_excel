<?php

namespace EventHandlers\Components;

use CBitrixComponent;
use CCrmOwnerType;

class CrmEntityDetailsFrameBeforeIncludeComponentTemplateHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     * @return void
     */
    public function handle(CBitrixComponent $component): void
    {
        if ($component->arParams['ENTITY_TYPE_ID'] == CCrmOwnerType::Company) {
            $component->arParams['DISABLE_TOP_MENU'] = 'Y';
        }
    }
}
