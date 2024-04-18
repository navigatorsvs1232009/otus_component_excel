<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\Context;
use Bitrix\Main\Request;

abstract class AbstractEventHandler
{
    protected Request $request;

    public function __construct()
    {
        $this->request = Context::getCurrent()->getRequest();
    }
}
