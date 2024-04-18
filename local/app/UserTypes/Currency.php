<?php

namespace UserTypes;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CUserTypeManager as UserType;
use Repositories\Currencies;

class Currency
{
    const USER_TYPE_ID = 'currency';
    const DESCRIPTION  = 'Currency';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => self::DESCRIPTION,
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE'     => self::class,
            'BASE_TYPE'     => UserType::BASE_TYPE_STRING,
            'CLASS_NAME'    => self::class,

            'GetPublicViewHTML'    => [self::class, 'getPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'getPublicViewHTML'],
            'GetPublicEditHTML'    => [self::class, 'getPublicEditHTML'],
            'GetPropertyFieldHtml' => [self::class, 'getPublicEditHTML'],

            'VIEW_CALLBACK' => [self::class, 'getPublicViewHTML'],
            'EDIT_CALLBACK' => [self::class, 'getPublicEditHTML'],
        ];
    }

    /**
     * @return string
     */
    public static function getDBColumnType(): string
    {
        return 'varchar(3)';
    }

    /**
     * @param array $field
     * @param array $value
     * @return string
     */
    public static function getPublicViewHTML(array $field, array $value = []): string
    {
        return $value['VALUE'] ?? $field['VALUE'] ?? 'N/A';
    }

    /**
     * @param  array  $field
     * @param  array  $value
     * @param  array  $HTMLControl
     *
     * @return string
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getPublicEditHTML(array $field, $value = [], $HTMLControl = []): string
    {
        global $APPLICATION;
        $field['USER_TYPE'] = self::class;

        ob_start();
        $APPLICATION->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField' => $field,
                'value'       => $value['VALUE'] ?? $field['VALUE'] ?? null,
                'htmlControl' => $HTMLControl['VALUE'],
                'currencies'  => Currencies::all(),
            ]
        );

        return ob_get_clean();
    }
}
