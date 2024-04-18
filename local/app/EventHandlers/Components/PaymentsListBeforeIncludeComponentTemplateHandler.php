<?php

namespace EventHandlers\Components;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;

class PaymentsListBeforeIncludeComponentTemplateHandler
{
    /**
     * @param  CBitrixComponent  $component
     *
     * @throws LoaderException
     */
    public function handle(CBitrixComponent $component): void
    {
        Extension::load(['element.lists.payments.add_to_bank_transaction']);
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getAddToBankTransactionButton($component);

        # скрываем ненужные кнопки
        $component->arResult['CAN_ADD_ELEMENT'] = false;
        $component->arResult['CAN_EDIT_SECTIONS'] = false;
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getAddToBankTransactionButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'               => Actions::CALLBACK,
                'CONFIRM'              => false,
                'DATA'                 => [
                    ['JS' => "BX.ScmPaymentsList.addRowsToBankTransaction('{$component->arResult['GRID_ID']}')"]
                ]
            ]
        );
        $button = new Button();
        $button->setClass(DefaultValue::SAVE_BUTTON_CLASS);
        $button->setId('grid_add_to_bank_transaction_button');
        $button->setOnchange($onchange);
        $button->setText('Add to Bank transaction');
        $button->setTitle('Add to Bank transaction');

        return $button->toArray();
    }
}
