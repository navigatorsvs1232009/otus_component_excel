<?php

namespace UserTypes;

use Repositories\StoresRepository;

class StockLink
{
    private const USER_TYPE_ID = 'stock_link';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => 'Stock link',
            'PROPERTY_TYPE' => 'E',
            'USER_TYPE'     => self::class,

            'GetPublicViewHTML'    => [self::class, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'GetPublicViewHTML'],
            'GetPublicEditHTML'    => [self::class, 'GetPublicEditHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPublicEditHTML'],
            'getUiFilter'          => [self::class, 'getUiFilter'],
        ];
    }

    /**
     * @param array $field
     * @param $value
     * @param $HTMLControl
     *
     * @return string
     */
    public static function GetPublicEditHTML(array $field, $value, $HTMLControl): string
    {
        global $APPLICATION;
        $field['USER_TYPE'] = self::class;

        ob_start();
        $APPLICATION->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField' => $field,
                'value'       => $value['VALUE'],
                'htmlControl' => $HTMLControl['VALUE'],
                'stocksRef'   => StoresRepository::all(),
            ]
        );

        return ob_get_clean();
    }

    /**
     * @param array $field
     * @param array $value
     *
     * @return string
     */
    public static function GetPublicViewHTML(array $field, array $value): string
    {
        static $storesRef = null;
        if (is_null($storesRef)) {
            $storesRef = StoresRepository::all();
        }

        $stockTitle = $storesRef[$value['VALUE']]['TITLE'] ?? '';

        return "<span>{$stockTitle}</span>";
    }

    /**
     * @param $field
     *
     * @return array
     */
    public static function getUiFilter($field): array
    {
        return [
            'id'     => $field['FIELD_ID'],
            'type'   => 'list',
            'name'   => $field['NAME'],
            'items'  => array_map(fn($store) => $store['TITLE'], StoresRepository::all()),
            'params' => ['multiple' => true],
        ];
    }
}
