<?php

namespace EventHandlers\IblockElement\Interfaces;

use CBitrixComponent;

interface OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface
{
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void;
}
