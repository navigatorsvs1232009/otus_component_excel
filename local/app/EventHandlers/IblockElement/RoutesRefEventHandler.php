<?php

namespace EventHandlers\IblockElement;

use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use Services\Infrastructure\TransportingRoutesRefHandler;

class RoutesRefEventHandler implements OnAfterUpdateEventHandlerInterface, OnAfterAddEventHandlerInterface, OnAfterDeleteEventHandlerInterface
{
    /**
     * @param $element
     *
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        (new TransportingRoutesRefHandler())->handleByRoute($element['ID']);
    }

    /**
     * @param $element
     *
     */
    public function onAfterUpdate($element): void
    {
        (new TransportingRoutesRefHandler())->handleByRoute($element['ID']);
    }

    /**
     * @param $element
     */
    public function onAfterDelete($element): void
    {
        (new TransportingRoutesRefHandler())->handleByRoute($element['ID']);
    }
}
