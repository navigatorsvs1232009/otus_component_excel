<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnAfterSetPropertyValuesEventHandlerInterface
{
    public function onAfterSetPropertyValues($elementId, $propertyValues): void;
}
