<?php

namespace UserTypes;

class DaysWeeks
{
    const USER_TYPE_ID = 'days_weeks';
    const DESCRIPTION  = 'Days (weeks)';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => self::DESCRIPTION,
            'PROPERTY_TYPE' => 'E', // чтобы тип колонки в mysql был int(11)
            'USER_TYPE'     => self::class,

            'GetPublicViewHTML'    => [self::class, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'GetPublicViewHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPropertyFieldHtml'],
            'GetPublicEditHTML'    => [self::class, 'GetPublicEditHTML'],
        ];
    }

    /**
     * @param array $field
     * @param array $value
     * @return string
     */
    public static function GetPublicViewHTML(array $field, array $value): string
    {
        if ($value['VALUE'] > 0) {
            $weeks = ceil($value['VALUE'] / 7);
            $html = "<span>{$value['VALUE']} d. ({$weeks} w.)</span>";
        } else {
            $html = '';
        }

        return $html;
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
}
