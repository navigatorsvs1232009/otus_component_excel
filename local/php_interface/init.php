<?php
use Bitrix\Main\EventManager;
EventManager::getInstance()->addEventHandler(
    'main',
    'OnEpilog',
    [StrahEvents::class, 'OnEpilog']
);

EventManager::getInstance()->addEventHandler(
    'main',
    'OnProlog',
    [StrahEvents::class, 'OnProlog']
);

class StrahEvents
{
    public static function OnProlog()
    {
        global $USER;
        $arJsConfig = array(
            'custom_start'=>array(
                'js'=>'/local/additional/main.js',
                'css'=>'/local/additional/main.css',
                'rel'=>array()
            )
        );
        foreach ($arJsConfig as $ext => $arExt) {
            \CJSCore::RegisterExt($ext, $arExt);
        }
        CUtil::InitJSCore(array('custom_start'));

    }


    public static function OnBeforePrologHandler()
    {
        CJSCore::Init(array('jquery2'));
    }

    public static function OnEpilog()
    {

    }

}

# автолоадер проекта
include_once __DIR__ .'/../app/autoload.php';

# автолоадер композера
include_once __DIR__ .'/vendor/autoload.php';
//
//# константы
//include_once __DIR__ .'/constants.php';
//
//# обработчики событий
//include_once __DIR__ .'/events.php';

//Подключение своих CSS и JS к Битрикс24

