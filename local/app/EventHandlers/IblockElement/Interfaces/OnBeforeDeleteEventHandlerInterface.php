<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnBeforeDeleteEventHandlerInterface
{
    public function onBeforeDelete(int $elementId): ?bool;
}
