<?php

namespace UserTypes;

use CUserTypeManager;

class VarCharString
{
    const USER_TYPE_ID = 'varchar_string';
    const DESCRIPTION  = 'String (varchar(255))';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID' => self::USER_TYPE_ID,
            'CLASS_NAME'   => self::class,
            'DESCRIPTION'  => self::DESCRIPTION,
            'BASE_TYPE'    => CUserTypeManager::BASE_TYPE_STRING,
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE'     => self::class,

            'GetPublicViewHTML'    => [self::class, 'getPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'getPublicViewHTML'],
            'GetPublicEditHTML'    => [self::class, 'getPublicEditHTML'],
            'GetPropertyFieldHtml' => [self::class, 'getPublicViewHTML'],

            'VIEW_CALLBACK' => [self::class, 'getPublicViewHTML'],
            'EDIT_CALLBACK' => [self::class, 'getPublicEditHTML'],
        ];
    }

    /**
     * @return string
     */
    public static function GetDBColumnType(): string
    {
        return 'varchar(255)';
    }

    /**
     * @param  array  $field
     * @param  array  $value
     *
     * @return string
     */
    public static function getPublicViewHTML(array $field, array $value = []): string
    {
        $value = $value['VALUE'] ?? $field['VALUE'] ?? '';

        return "<span>{$value}</span>";
    }

    /**
     * @param  array  $field
     * @param  array  $value
     *
     * @return string
     */
    public static function getPublicEditHTML(array $field, array $value = []): string
    {
        $value = $value['VALUE'] ?? $field['VALUE'] ?? '';
        $controlName = $value['htmlControl'] ?? $field['FIELD_NAME'];
        $controlId = $field['FIELD_NAME'];

        return "<span class='fields string field-wrap'><span class='fields string field-item'><input name='{$controlName}' id='{$controlId}' value='{$value}'></span></span>";
    }
}
