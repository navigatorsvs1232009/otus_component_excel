<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\Me1C\IncotermsPlaceExportAgent;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;

class IncotermsPlaceEventHandler implements OnAfterUpdateEventHandlerInterface, OnAfterAddEventHandlerInterface, OnAfterDeleteEventHandlerInterface
{
    /**
     * @param $element
     */
    public function onAfterDelete($element): void
    {
        IncotermsPlaceExportAgent::scheduleRunOnce($element['ID']);
    }

    /**
     * @param $element
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        IncotermsPlaceExportAgent::scheduleRunOnce($element['ID']);
    }

    /**
     * @param $element
     */
    public function onAfterUpdate($element): void
    {
        IncotermsPlaceExportAgent::scheduleRunOnce($element['ID']);
    }
}
