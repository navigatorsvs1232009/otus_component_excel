<?php

require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle('Таблица');

$APPLICATION->IncludeComponent(
    'bitrix:crm.interface.toolbar',
    'title',
    [
        'TOOLBAR_ID' => 'CLIENT_VIEW',
        'BUTTONS' => [
            [
                'TEXT' => 'Выгрузить в Excel',
                'TITLE' => 'Выгрузить в Excel',
                'LINK' => '/tableview/?template=excel',
                'ICON' => 'btn-export'
            ]
        ]
    ]
);

$APPLICATION->IncludeComponent(
    'macro:table.view',
    'list',
    []
);

require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");