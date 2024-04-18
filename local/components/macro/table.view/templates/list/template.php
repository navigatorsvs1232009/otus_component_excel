<?php

$grid_options = new Bitrix\Main\Grid\Options('report_list');
$nav_params = $grid_options->GetNavParams();
$nav = new Bitrix\Main\UI\PageNavigation('report_list');
$nav->allowAllRecords(false)
    ->setPageSize($nav_params['nPageSize'])
    ->initFromUri();
$nav->setRecordCount($arResult['COUNT']);

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    array(
        'GRID_ID' => 'MY_GRID_ID',
        'COLUMNS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['LIST'],
        'NAV_OBJECT' => $nav,
//        'AJAX_MODE' => 'Y',
//        'AJAX_OPTION_JUMP' => 'N',
//        'AJAX_OPTION_HISTORY' => 'N',
//        'SHOW_ROW_CHECKBOXES' => $arParams['SHOW_CHECKBOXES'],

    )
);


?>