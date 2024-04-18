<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnBeforeUpdateEventHandlerInterface
{
    public function onBeforeUpdate(&$element): ?bool;
}
