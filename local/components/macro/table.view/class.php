<?php

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main;
use \Models\ClientTable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;

class OtusTableComponent extends CBitrixComponent implements Controllerable
{
    private function _checkModules()
    {
        if(!Loader::includeModule("iblock")
            || !Loader::includeModule("crm")
        ) {
            throw new \Exception('Не загружены модули необходимые для работы компонента');
        }
        return true;
    }

    public function configureActions()
    {
        return [
            'getCount' => [
                'prefilters' => [],
            ],
        ];
    }

    public function getCountAction()
    {
        return ['result' => ClientTable::getCount()];
    }

    public function onPrepareComponentParams($arParams)
    {
        if(isset($arParams['SHOW_CHECKBOXES']) && $arParams['SHOW_CHECKBOXES'] === 'Y'){
            $arParams['SHOW_CHECKBOXES'] = true;
        }
        else{
            $arParams['SHOW_CHECKBOXES'] = false;
        }
        return $arParams;
    }

    public function executeComponent()
    {
        $this->_checkModules();
        $this->setColumn();
        $this->_request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

        if(isset($this->_request['report_list'])){
            $page = explode('page', $this->_request['report_list']);
            $page=$page[1];
        }
        else{
            $page=1;
        }
        $limit = 20;
        $this->setList($page);

        if ($this->_request['template'] === 'excel'){
            $this->proceedExcel();
        }

        $this->arResult['COUNT'] = (ClientTable::getCount());
        $this->includeComponentTemplate();
    }

    public function setColumn()
    {
        $fieldmap = ClientTable::getMap();
        foreach ($fieldmap as $field) {
            $this->arResult['COLUMNS'][] = array(
                'id' => $field->getName(),
                'name'=>$field->getTitle(),
                );
        }
    }

    private function setList($page = 1, $limit = 20)
    {
        $data = ClientTable::getList(
            array(
                'order' => array('ID' => 'DESC'),
                'limit' => $limit,
                'offset' => $limit*($page-1),
            )
        );
        while ($row = $data->fetch()) {
            $this->arResult['LIST'][] = [
                'data' => $row,
            ] ;
        }
    }
    private function proceedExcel()
    {
        $this->setTemplateName("excel");
        $this->includeComponentTemplate();

    }

}