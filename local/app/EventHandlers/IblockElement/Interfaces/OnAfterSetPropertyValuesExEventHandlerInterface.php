<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnAfterSetPropertyValuesExEventHandlerInterface
{
    public function onAfterSetPropertyValuesEx($elementId, $propertyValues): void;
}
