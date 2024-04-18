<?php

namespace UserTypes\Delivery;

use Models\SCM\DeliveryPropertyValuesTable;

class ContainerType
{
    const USER_TYPE_ID = 'delivery_container_type';
    public const CONTAINER_TYPES = [
        '20'   => "20'DC",
        '20FT' => "20'FT",
        '20OT' => "20'OT",
        '40'   => "40'DC/HQ",
        '40FT' => "40'FT",
        '40OT' => "40'OT",
    ];

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => 'Delivery container type',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE'     => self::class,

            'GetPublicViewHTMLMulty' => [self::class, 'GetPublicViewHTMLMulty'],
            'GetAdminListViewHTML'   => [self::class, 'GetPublicViewHTML'],
            'GetPublicEditHTMLMulty' => [self::class, 'GetPublicEditHTMLMulty'],
            'GetPropertyFieldHtml'   => [self::class, 'GetPublicEditHTML'],

            'ConvertToDB'          => [static::class, 'ConvertToDB'],
            'ConvertFromDB'        => [static::class, 'ConvertFromDB'],
        ];
    }

    /**
     * @param  array  $property
     * @param  array  $value
     *
     * @return array|null
     */
    public static function ConvertToDB(array $property, array $value): ?array
    {
        $value = json_encode($value['VALUE']);

        return $value ? ['VALUE' => $value] : null;
    }

    /**
     * @param array $property
     * @param array $value
     * @return array|null
     */
    public static function ConvertFromDB(array $property, array $value): ?array
    {
        $value = json_decode($value['VALUE'], true);

        return ['VALUE' => is_array($value) ? $value :  null];
    }

    /**
     * @param  array  $field
     * @param  ?array  $values
     * @param  array  $HTMLControl
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetPublicEditHTMLMulty(array $field, ?array $values, array $HTMLControl = []) : string
    {
        $field['USER_TYPE'] = self::class;

        $options = array_map( # так сделано, чтобы порядок опций не зависел от ключей
            fn($name, $type) => ['type' => $type, 'name' => $name],
            self::CONTAINER_TYPES,
            array_keys(self::CONTAINER_TYPES)
        );

        if (isset($field['ELEMENT_ID'])) {
            $delivery = DeliveryPropertyValuesTable::query()
                ->setSelect(['TRANSPORT_TYPE_NAME' => 'TRANSPORT_TYPE.NAME'])
                ->where('IBLOCK_ELEMENT_ID', $field['ELEMENT_ID'])
                ->fetch();

            if ($delivery && $delivery['TRANSPORT_TYPE_NAME'] === 'Railway') {
                $options = array_filter(
                    $options,
                    fn($option) => $option['type'] === 40
                );
            }
        }

        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField' => $field,
                'data'        => [
                    'values'      => array_filter(array_column($field['VALUE'], 'VALUE')),
                    'controlName' => $HTMLControl['VALUE'],
                    'options'     => $options,
                ],
            ]
        );

        return ob_get_clean();
    }

    /**
     * @param  array  $field
     * @param  array|null  $values
     * @param  array  $HTMLControl
     *
     * @return string
     */
    public static function GetPublicViewHTMLMulty(array $field, ?array $values, array $HTMLControl = []): string
    {
        $values = array_filter(
            array_map(
                fn($item) => json_decode($item, true),
                ($field['VALUE'] ?? $values['VALUE']) ?: []
            )
        );

        return join('<br>', array_map(
                fn($item) => self::CONTAINER_TYPES[$item['type']],
                $values
            )
        );
    }
}
