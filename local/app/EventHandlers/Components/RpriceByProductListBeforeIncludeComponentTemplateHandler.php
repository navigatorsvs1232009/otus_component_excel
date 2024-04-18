<?php

namespace EventHandlers\Components;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\DefaultValue;
use Bitrix\Main\Grid\Panel\Snippet\Button;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;
use Bitrix\Main\Grid;
use Models\SCM\RPRiceProductRowsPropertyValuesTable;

class RpriceByProductListBeforeIncludeComponentTemplateHandler
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
        # убираем все штатные кнопки
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'] = [];

        # кнопка Engage
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getEngageButton($component);
        Extension::load('element.lists.rprice_by_product.engage');

        # кнопка Delegate
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getDelegateButton($component);
        Extension::load('element.lists.rprice_by_product.delegate');

        # кнопка создания RFQ
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getCreateRfqButton($component);
        Extension::load('element.lists.rprice_by_product.create_rfq');

        # установка статуса KD in progress
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getKdInProgress($component);
        Extension::load('element.lists.rprice_by_product.kd_in_progress');

        # создание Design work
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getDesignWorkCreateButton($component);
        Extension::load('element.lists.rprice_by_product.create_design_work');

        # групповое действие
        $component->arResult['GRID_ACTION_PANEL']['GROUPS'][0]['ITEMS'][] = $this->getGroupActionsDropdown($component);
        Extension::load('element.lists.rprice_by_product.group_actions');

        # скрипт выбора RFQ
        Extension::load('element.lists.rprice_by_product.select_rfq');

        $rows = $this->getRows(array_column($component->arResult['ELEMENTS_ROWS'], 'id'));
        $this->handleBlockedRows($component, $rows);

        # скрываем ненужные кнопки
        $component->arResult['CAN_ADD_ELEMENT'] = false;
        $component->arResult['CAN_EDIT_SECTIONS'] = false;
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getCreateRfqButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.RpriceByProductList.createRfq('{$component->arResult['GRID_ID']}')"],
                ],
            ]
        );
        $button = new Button();
        $button->setClass(DefaultValue::SAVE_BUTTON_CLASS);
        $button->setId('creat_rfq_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('Create RFQ');
        $button->setTitle('Create RFQ');

        return $button->toArray();
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getKdInProgress(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.RpriceByProductList.setKdInProgressStatus('{$component->arResult['GRID_ID']}')"],
                ],
            ]
        );
        $button = new Button();
        $button->setClass(DefaultValue::EDIT_BUTTON_CLASS);
        $button->setId('kd_in_progress_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('KD in progress');
        $button->setTitle('KD in progress');

        return $button->toArray();
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getGroupActionsDropdown(CBitrixComponent $component): array
    {
        return [
            'ID'    => 'action_dropdown_'. $component->arResult['GRID_ID'],
            'NAME'  => 'action_dropdown_'. $component->arResult['GRID_ID'],
            'TYPE'  => Grid\Panel\Types::DROPDOWN,
            'ITEMS' => $this->getGroupActionsDropdownItems($component),
        ];
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array[]
     */
    private function getGroupActionsDropdownItems(CBitrixComponent $component): array
    {
        return [
            [
                'NAME'     => 'Select action',
                'VALUE'    => 'none',
                'ONCHANGE' => [
                    [
                        'ACTION' => Grid\Panel\Actions::RESET_CONTROLS,
                    ],
                ],
            ],
            [
                'NAME'     => 'Set No data',
                'VALUE'    => 'setNoData',
                'ONCHANGE' => [
                    [
                        'ACTION' => Grid\Panel\Actions::CREATE,
                        'DATA'   => [
                            [
                                'TYPE'  => Grid\Panel\Types::DROPDOWN,
                                'ID'    => 'action_setNoData',
                                'NAME'  => 'value',
                                'ITEMS' => [
                                    [
                                        'NAME'  => 'Yes',
                                        'VALUE' => 'Y',
                                    ],
                                    [
                                        'NAME'  => 'No',
                                        'VALUE' => 'N',
                                    ],
                                ],
                            ],
                            (new Grid\Panel\Snippet())->getApplyButton([
                                'ONCHANGE' => [
                                    [
                                        'ACTION' => Grid\Panel\Actions::CALLBACK,
                                        'DATA'   => [
                                            ['JS' => "BX.RpriceByProductList.applyGroupAction('{$component->arResult['GRID_ID']}', 'setNoData')"],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            [
                'NAME'     => 'Set additional time, w',
                'VALUE'    => 'setAdditionalTime',
                'ONCHANGE' => [
                    [
                        'ACTION' => Grid\Panel\Actions::CREATE,
                        'DATA'   => [
                            [
                                'TYPE' => Grid\Panel\Types::TEXT,
                                'ID'   => 'action_setAdditionalTime',
                                'NAME' => 'value',
                            ],
                            (new Grid\Panel\Snippet())->getApplyButton([
                                'ONCHANGE' => [
                                    [
                                        'ACTION' => Grid\Panel\Actions::CALLBACK,
                                        'DATA'   => [
                                            ['JS' => "BX.RpriceByProductList.applyGroupAction('{$component->arResult['GRID_ID']}', 'setAdditionalTime')"],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            [
                'NAME'     => 'Set special transport time, w',
                'VALUE'    => 'setSpecialTransportTime',
                'ONCHANGE' => [
                    [
                        'ACTION' => Grid\Panel\Actions::CREATE,
                        'DATA'   => [
                            [
                                'TYPE' => Grid\Panel\Types::TEXT,
                                'ID'   => 'action_setSpecialTransportTime',
                                'NAME' => 'value',
                            ],
                            (new Grid\Panel\Snippet())->getApplyButton([
                                'ONCHANGE' => [
                                    [
                                        'ACTION' => Grid\Panel\Actions::CALLBACK,
                                        'DATA'   => [
                                            ['JS' => "BX.RpriceByProductList.applyGroupAction('{$component->arResult['GRID_ID']}', 'setSpecialTransportTime')"],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            [
                'NAME'     => 'Set comment',
                'VALUE'    => 'setComment',
                'ONCHANGE' => [
                    [
                        'ACTION' => Grid\Panel\Actions::CREATE,
                        'DATA'   => [
                            [
                                'TYPE' => Grid\Panel\Types::TEXT,
                                'ID'   => 'action_setComment',
                                'NAME' => 'value',
                            ],
                            (new Grid\Panel\Snippet())->getApplyButton([
                                'ONCHANGE' => [
                                    [
                                        'ACTION' => Grid\Panel\Actions::CALLBACK,
                                        'DATA'   => [
                                            ['JS' => "BX.RpriceByProductList.applyGroupAction('{$component->arResult['GRID_ID']}', 'setComment')"],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getEngageButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.RpriceByProductList.engage('{$component->arResult['GRID_ID']}')"],
                ],
            ]
        );
        $button = new Button();
        $button->setId('engage_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('Engage');
        $button->setTitle('Engage');

        return $button->toArray();
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getDelegateButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.RpriceByProductList.delegate('{$component->arResult['GRID_ID']}')"],
                ],
            ]
        );
        $button = new Button();
        $button->setId('delegate_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('Delegate');
        $button->setTitle('Delegate');

        return $button->toArray();
    }

    /**
     * @param  CBitrixComponent  $component
     * @param  array  $rows
     */
    private function handleBlockedRows(CBitrixComponent $component, array $rows): void
    {
        foreach ($component->arResult['ELEMENTS_ROWS'] as &$row) {
            $row['editable'] = !$rows[$row['id']]['BLOCKED'];
        }
    }

    /**
     * @param  array  $rowIds
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getRows(array $rowIds): array
    {
        $dbResult = RPRiceProductRowsPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID', 'BLOCKED'],
            'filter' => ['=IBLOCK_ELEMENT_ID' => $rowIds]
        ]);
        while ($row = $dbResult->fetch()) {
            $rows[$row['IBLOCK_ELEMENT_ID']] = $row;
        }

        return $rows ?? [];
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @return array
     */
    private function getDesignWorkCreateButton(CBitrixComponent $component): array
    {
        $onchange = new Onchange();
        $onchange->addAction(
            [
                'ACTION'  => Actions::CALLBACK,
                'CONFIRM' => false,
                'DATA'    => [
                    ['JS' => "BX.RpriceByProductList.createDesignWork('{$component->arResult['GRID_ID']}')"],
                ],
            ]
        );
        $button = new Button();
        $button->setId('design_work_create_button_'.$component->arResult['GRID_ID']);
        $button->setOnchange($onchange);
        $button->setText('Create Design work');
        $button->setTitle('Create Design work');

        return $button->toArray();
    }
}
