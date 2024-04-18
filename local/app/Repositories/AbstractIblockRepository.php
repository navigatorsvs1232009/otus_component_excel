<?php

namespace Repositories;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;

abstract class AbstractIblockRepository
{
    static bool $useCache = true;

    /**
     * @param  string|null  $byKey
     * @param  array  $filter
     * @param  array  $select
     * @param  array  $order
     *
     * @return array
     */
    public static function all(?string $byKey = 'ID', array $filter = [], array $select = [], array $order = ['SORT' => 'ASC']): array
    {
        global $CACHE_MANAGER;

        $cache = Cache::createInstance();
        $uniqueString = md5(static::CACHE_DIR.$byKey.json_encode($filter).json_encode($select).json_encode($order));
        if (static::$useCache && $cache->initCache(3600000, $uniqueString, static::CACHE_DIR)) {
            $rows = $cache->getVars();

        } elseif ($cache->startDataCache()) {
            $CACHE_MANAGER->StartTagCache(static::CACHE_DIR);

            $filter['IBLOCK_ID'] = static::IBLOCK_ID;
            $select = array_merge($select, [
                'ID',
                'NAME',
                'CODE',
                'XML_ID',
                'IBLOCK_ID',
                'ACTIVE',
                'SORT'
            ]);

            $dbResult = CIBlockElement::GetList(
                $order,
                $filter,
                false,
                false,
                $select
            );
            while ($row = $dbResult->Fetch()) {
                if (isset($byKey)) {
                    $rows[$row[$byKey]] = $row;
                } else {
                    $rows[] = $row;
                }
            }

            $CACHE_MANAGER->RegisterTag('iblock_id_' . static::IBLOCK_ID);
            $CACHE_MANAGER->EndTagCache();

            if (empty($rows)) {
                $cache->abortDataCache();
            } else {
                $cache->endDataCache($rows);
            }
        }

        static::$useCache = true;

        return $rows ?? [];
    }

    /**
     * @param  string  $propertyCode
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function propertyId(string $propertyCode): int
    {
        static $cache = [];

        if (array_key_exists($propertyCode, $cache)) {
            return $cache[$propertyCode];
        }

        $cache[$propertyCode] = PropertyTable::query()
            ->setSelect(['ID'])
            ->where('IBLOCK_ID', static::IBLOCK_ID)
            ->where('CODE', $propertyCode)
            ->fetch()['ID'] ?? null;

        return $cache[$propertyCode];
    }

    /**
     * @return string
     */
    public static function withoutCache(): string
    {
        static::$useCache = false;

        return static::class;
    }

    /**
     * @return string
     */
    public static function getListUrl(): string
    {
        return sprintf('/services/lists/%d/view/0/', static::IBLOCK_ID);
    }

    /**
     * @param  int  $elementId
     *
     * @return string
     */
    public static function getListElementUrl(int $elementId): string
    {
        return sprintf('/services/lists/%d/element/0/%d/', static::IBLOCK_ID, $elementId);
    }
}
