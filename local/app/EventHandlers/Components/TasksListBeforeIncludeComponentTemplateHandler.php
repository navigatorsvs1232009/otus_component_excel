<?php

namespace EventHandlers\Components;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;

class TasksListBeforeIncludeComponentTemplateHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     * @throws LoaderException
     */
    public function handle(CBitrixComponent $component): void
    {
        $component->arResult['GROUP_ACTIONS']['GROUPS'][0]['ITEMS'][] = $this->getAttachToDeliveryButton($component);
        Extension::load('element.tasks.list.attach_to_delivery');
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getAttachToDeliveryButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'               => Actions::CALLBACK,
                'CONFIRM'              => false,
                'DATA'                 => [
                    ['JS' => "BX.TasksList.attachRowsToDelivery('{$component->arParams['GRID_ID']}')"]
                ]
            ]
        );
        $button = new Button();
        $button->setClass(DefaultValue::SAVE_BUTTON_CLASS);
        $button->setId('grid_attach_to_delivery_button');
        $button->setOnchange($onchange);
        $button->setText('Attach to delivery');
        $button->setTitle('Attach to delivery');

        return $button->toArray();
    }
}
