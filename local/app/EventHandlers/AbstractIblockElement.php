<?php

namespace EventHandlers;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CBitrixComponent;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesExEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use EventHandlers\IblockElement\EventHandlerFactory;
use EventHandlers\IblockElement\Interfaces\OnSetPropertyValuesEventHandlerInterface;

abstract class AbstractIblockElement
{
    private static array $handlers = [];

    /**
     * @param array $element
     */
    public static function onAfterAddDispatcher(array $element): void
    {
        $handler = self::$handlers[$element['IBLOCK_ID']] ?? self::$handlers[$element['IBLOCK_ID']] = EventHandlerFactory::create($element['IBLOCK_ID']);
        if ($handler instanceof OnAfterAddEventHandlerInterface) {
            $handler->onAfterAdd($element);
        }
    }

    /**
     * @param array $element
     */
    public static function onAfterUpdateDispatcher(array $element): void
    {
        $handler = self::$handlers[$element['IBLOCK_ID']] ?? self::$handlers[$element['IBLOCK_ID']] = EventHandlerFactory::create($element['IBLOCK_ID']);
        if ($handler instanceof OnAfterUpdateEventHandlerInterface) {
            $handler->onAfterUpdate($element);
        }
    }

    /**
     * @param array $element
     */
    public static function onAfterDeleteDispatcher(array $element): void
    {
        $handler = self::$handlers[$element['IBLOCK_ID']] ?? self::$handlers[$element['IBLOCK_ID']] = EventHandlerFactory::create($element['IBLOCK_ID']);
        if ($handler instanceof OnAfterDeleteEventHandlerInterface) {
            $handler->onAfterDelete($element);
        }
    }

    /**
     * @param  array  $element
     *
     * @return bool|null
     */
    public static function onBeforeUpdateDispatcher(array &$element): ?bool
    {
        $handler = self::$handlers[$element['IBLOCK_ID']] ?? self::$handlers[$element['IBLOCK_ID']] = EventHandlerFactory::create($element['IBLOCK_ID']);

        return ($handler instanceof OnBeforeUpdateEventHandlerInterface) ? $handler->onBeforeUpdate($element) : null;
    }

    /**
     * @param  int  $elementId
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onBeforeDeleteDispatcher(int $elementId): ?bool
    {
        $handler = self::$handlers[$elementId] ?? null;
        if (empty($handler)) {
            $element = ElementTable::getList(['select' => ['ID', 'IBLOCK_ID'], 'filter' => ['ID' => $elementId]])->fetch();
            $handler = self::$handlers[$element['IBLOCK_ID']] = EventHandlerFactory::create($element['IBLOCK_ID']);
        }

        return ($handler instanceof OnBeforeDeleteEventHandlerInterface) ? $handler->onBeforeDelete($elementId) : null;
    }

    /**
     * @param  array  $element
     *
     * @return bool|null
     */
    public static function onBeforeAddDispatcher(array &$element): ?bool
    {
        $handler = self::$handlers[$element['IBLOCK_ID']] ?? self::$handlers[$element['IBLOCK_ID']] = EventHandlerFactory::create($element['IBLOCK_ID']);

        return ($handler instanceof OnBeforeAddEventHandlerInterface) ? $handler->onBeforeAdd($element) : null;
    }

    /**
     * @param $elementId
     * @param $iblockId
     * @param $propertyValues
     */
    public static function onSetPropertyValuesDispatcher($elementId, $iblockId, $propertyValues): void
    {
        $handler = self::$handlers[$iblockId] ?? self::$handlers[$iblockId] = EventHandlerFactory::create($iblockId);
        if ($handler instanceof OnSetPropertyValuesEventHandlerInterface) {
            $handler->onSetPropertyValues($elementId, $propertyValues);
        }
    }

    /**
     * @param $elementId
     * @param $iblockId
     * @param $propertyValues
     *
     * @return mixed
     */
    public static function onAfterSetPropertyValuesExDispatcher($elementId, $iblockId, $propertyValues): void
    {
        $handler = self::$handlers[$iblockId] ?? self::$handlers[$iblockId] = EventHandlerFactory::create($iblockId);
        if ($handler instanceof OnAfterSetPropertyValuesExEventHandlerInterface) {
            $handler->onAfterSetPropertyValuesEx($elementId, $propertyValues);
        }
    }

    /**
     * @param $elementId
     * @param $iblockId
     * @param $propertyValues
     *
     * @return mixed
     */
    public static function onAfterSetPropertyValuesDispatcher($elementId, $iblockId, $propertyValues): void
    {
        $handler = self::$handlers[$iblockId] ?? self::$handlers[$iblockId] = EventHandlerFactory::create($iblockId);
        if ($handler instanceof OnAfterSetPropertyValuesEventHandlerInterface) {
            $handler->onAfterSetPropertyValues($elementId, $propertyValues);
        }
    }

    /**
     * @param  CBitrixComponent  $component
     */
    public static function onBeforeListElementEditFormFieldsPreparedDispatcher(CBitrixComponent $component): void
    {
        $handler = self::$handlers[$component->arResult['IBLOCK_ID']] ?? EventHandlerFactory::create($component->arResult['IBLOCK_ID']);
        if ($handler instanceof onBeforeListElementEditFormFieldsPreparedEventHandlerInterface) {
            $handler->onBeforeListElementEditFormFieldsPrepared($component);
        }
    }
}
