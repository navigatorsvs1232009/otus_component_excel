<?php

namespace UserTypes;

use CUserTypeManager as UserType;

class YesNo
{
    const USER_TYPE_ID = 'yes_no';
    const DESCRIPTION  = 'Yes/No';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => self::DESCRIPTION,
            'PROPERTY_TYPE' => 'I',
            'USER_TYPE'     => self::class,
            'BASE_TYPE'     => UserType::BASE_TYPE_INT,
            'CLASS_NAME'    => self::class,

            'GetPublicViewHTML'    => [self::class, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'GetPublicViewHTML'],
            'GetPublicEditHTML'    => [self::class, 'GetPublicEditHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPublicEditHTML'],
            'AddFilterFields'      => [self::class, 'addFilterFields'],
            'getUiFilter'          => [self::class, 'getUiFilter'],

            'VIEW_CALLBACK' => [self::class, 'GetPublicViewHTML'],
            'EDIT_CALLBACK' => [self::class, 'GetPublicEditHTML'],
        ];
    }

    /**
     * @return string
     */
    public static function getDBColumnType(): string
    {
        return 'tinyint(1)';
    }

    /**
     * @param array $field
     * @param array $value
     * @return string
     */
    public static function GetPublicViewHTML(array $field, array $value = []): string
    {
        return !empty($value['VALUE']) ? 'Yes' : (!empty($field['VALUE']) ? 'Yes' : 'No');
    }

    /**
     * @param  array  $field
     * @param  array|null  $value
     * @param  array  $HTMLControl
     *
     * @return string
     */
    public static function GetPublicEditHTML(array $field, ?array $value = [], array $HTMLControl = []): string
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
                'htmlControl' => $HTMLControl['VALUE']
            ]
        );

        return ob_get_clean();
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
            'items'  => [
                'Y' => ['NAME' => 'Yes'],
                'N' => ['NAME' => 'No'],
            ],
            'params' => ['multiple' => false],
        ];
    }

    /**
     * @param $property
     * @param $controlSettings
     * @param $filter
     * @param $filtered
     */
    public static function addFilterFields($property, $controlSettings, &$filter, &$filtered)
    {
        if ($filter[$property['FIELD_ID']] === 'Y') {
            unset($filter[$property['FIELD_ID']]);
            $filter['!='.$property['FIELD_ID']] = false;
        }

        if ($filter[$property['FIELD_ID']] === 'N') {
            unset($filter[$property['FIELD_ID']]);
            $filter['='.$property['FIELD_ID']] = false;
        }
    }
}
