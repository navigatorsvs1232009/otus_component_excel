<?php

namespace UserTypes;

class StringWithDate
{
    const USER_TYPE_ID = 'string_with_date';
    const DESCRIPTION = 'String with date';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => static::USER_TYPE_ID,
            'DESCRIPTION'   => static::DESCRIPTION,
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE'     => static::class,

            'GetPublicViewHTML'    => [static::class, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [static::class, 'GetPublicViewHTML'],
            'GetPublicEditHTML'    => [static::class, 'GetPublicEditHTML'],
            'GetPropertyFieldHtml' => [static::class, 'GetPublicEditHTML'],
            'ConvertToDB'          => [static::class, 'ConvertToDB'],
            'ConvertFromDB'        => [static::class, 'ConvertFromDB'],

            'AddFilterFields'      => [self::class, 'addFilterFields'],
        ];
    }

    /**
     * @param array $field
     * @param $value
     * @param $HTMLControl
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
                'htmlControl' => $HTMLControl['VALUE']
            ]
        );

        return ob_get_clean();
    }

    /**
     * @param array $field
     * @param array $value
     * @return string
     */
    public static function GetPublicViewHTML(array $field, array $value): string
    {
        if (!empty($value['VALUE']) && !is_array($value['VALUE'])) {
            $value = self::ConvertFromDB([], $value);
        }

        if (is_array($value['VALUE'])) {
            return "<span>{$value['VALUE']['STRING']} / {$value['VALUE']['DATE']}</span>";
        }

        return '';
    }

    /**
     * @param  array  $property
     * @param  array  $value
     *
     * @return array
     */
    public static function ConvertToDB(array $property, array $value): array
    {
        return ['VALUE' => json_encode($value['VALUE'])];
    }

    /**
     * @param array $property
     * @param array $value
     * @return array|null
     */
    public static function ConvertFromDB(array $property, array $value): ?array
    {
        return ['VALUE' => json_decode($value['VALUE'], true)];
    }

    /**
     * @param $property
     * @param $controlSettings
     * @param $filter
     * @param $filtered
     */
    public static function addFilterFields($property, $controlSettings, &$filter, &$filtered)
    {
        if (isset($filter[$property['FIELD_ID']])) {
            $filter['%'.$property['FIELD_ID']] = $filter[$property['FIELD_ID']];
            unset($filter[$property['FIELD_ID']]);
        }
    }
}
