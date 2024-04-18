<?php

namespace EventHandlers\IblockElement\Interfaces;

interface OnSetPropertyValuesEventHandlerInterface
{
    public function onSetPropertyValues($elementId, $propertyValues): void;
}
