<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnBeforeAddEventHandlerInterface
{
    public function onBeforeAdd(&$element): ?bool;
}