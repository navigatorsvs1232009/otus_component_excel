<?php

namespace EventHandlers\Components;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;
use Models\SCM\DeliveryPropertyValuesTable;

class DeliveryListBeforeIncludeComponentTemplateHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     * @throws LoaderException
     */
    public function handle(CBitrixComponent $component): void
    {
        # убираем все штатные кнопки
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'] = [];

        # кнопка Set tasks comment
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getIsTasksCommentButton($component);
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getQaTasksCommentButton($component);
        Extension::load('element.lists.delivery.tasks_comment');
        Extension::load('element.lists.delivery.grid');

        $deliveryStatusFieldId = DeliveryPropertyValuesTable::getPropertyId('DELIVERY_STATUS_ID');
        foreach ($component->arResult['ELEMENTS_ROWS'] as &$row) {
            if ($row['columns']["PROPERTY_{$deliveryStatusFieldId}"] === 'Finished') {
                foreach ($component->arResult['ELEMENTS_HEADERS'] as $column) {
                    $row['columnClasses'][$column['id']] = 'delivery-status-finished';
                }
            }
        }
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getIsTasksCommentButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.DeliveryList.setTasksComment('{$component->arResult['GRID_ID']}')"],
                ],
            ]
        );
        $button = new Button();
        $button->setClass(DefaultValue::EDIT_BUTTON_CLASS);
        $button->setId('set_is_tasks_comment_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('Set IS tasks comment');

        return $button->toArray();
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getQaTasksCommentButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.DeliveryList.setTasksComment('{$component->arResult['GRID_ID']}', 'QA')"],
                ],
            ]
        );
        $button = new Button();
        $button->setClass(DefaultValue::EDIT_BUTTON_CLASS);
        $button->setId('set_qa_tasks_comment_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('Set QA tasks comment');

        return $button->toArray();
    }
}
