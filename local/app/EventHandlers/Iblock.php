<?php

namespace EventHandlers;

use Models\AbstractIblockPropertyValuesTable;
use Services\Infrastructure\IblockConstantsHandler;

class Iblock
{
    /**
     * @param  array  $property
     */
    public static function onAfterIBlockPropertyAdd(array $property): void
    {
        AbstractIblockPropertyValuesTable::clearPropertyMapCache($property['IBLOCK_ID']);
    }

    /**
     * @param  array  $property
     */
    public static function onAfterIBlockPropertyUpdate(array $property): void
    {
        AbstractIblockPropertyValuesTable::clearPropertyMapCache($property['IBLOCK_ID']);
    }

    /**
     * @param  array  $property
     */
    public static function onAfterIBlockPropertyDelete(array $property): void
    {
        AbstractIblockPropertyValuesTable::clearPropertyMapCache($property['IBLOCK_ID']);
    }

    /**
     * @param  array  $iblock
     */
    public static function onAfterIBlockAdd(array $iblock): void
    {
        self::handleIblockConstants();
    }

    /**
     * @param  array  $iblock
     */
    public static function onAfterIBlockUpdate(array $iblock): void
    {
        self::handleIblockConstants();
    }

    /**
     * @param  array  $iblock
     */
    public static function onAfterIBlockDelete(array $iblock): void
    {
        self::handleIblockConstants();
    }

    /**
     *
     */
    private static function handleIblockConstants(): void
    {
        (new IblockConstantsHandler($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/iblock_constants.php'))->run();
    }
}
