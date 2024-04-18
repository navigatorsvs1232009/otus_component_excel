<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnAfterDeleteEventHandlerInterface
{
    public function onAfterDelete($element): void;
}
