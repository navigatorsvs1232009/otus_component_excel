<?php

namespace UserTypes\Delivery;

use Models\SCM\DeliveryLoadingTypeRefPropertyValuesTable;
use Models\SCM\DeliveryPropertyValuesTable;

class LoadingType
{
    const USER_TYPE_ID = 'delivery_loading_type';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => 'Delivery loading type',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE'     => self::class,

            'GetPublicViewHTML'    => [self::class, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'GetPublicViewHTML'],
            'GetPublicEditHTML'    => [self::class, 'GetPublicEditHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPublicEditHTML'],
            'getUiFilter'          => [self::class, 'getUiFilter'],
        ];
    }

    /**
     * @param  array  $field
     * @param $value
     * @param $HTMLControl
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetPublicEditHTML(array $field, $value, $HTMLControl): string
    {
        global $APPLICATION;
        $field['USER_TYPE'] = self::class;

        if (isset($field['ELEMENT_ID'])) {
            $delivery = DeliveryPropertyValuesTable::query()
                ->setSelect(['TRANSPORT_TYPE_ID'])
                ->where('IBLOCK_ELEMENT_ID', $field['ELEMENT_ID'])
                ->fetch();
        }

        ob_start();
        $APPLICATION->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField' => $field,
                'value'       => $value['VALUE'],
                'htmlControl' => $HTMLControl['VALUE'],
                'options'     => self::getDeliveryLoadingTypeRef($delivery['TRANSPORT_TYPE_ID'] ?? null),
            ]
        );

        return ob_get_clean();
    }

    /**
     * @param  array  $field
     * @param  array  $value
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetPublicViewHTML(array $field, array $value): string
    {
        static $deliveryLoadingTypeRef = null;
        if (is_null($deliveryLoadingTypeRef)) {
            $deliveryLoadingTypeRef = self::getDeliveryLoadingTypeRef();
        }

        $name = $deliveryLoadingTypeRef[$value['VALUE']]['NAME'] ?? '';

        return "<span>{$name}</span>";
    }

    /**
     * @param $field
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getUiFilter($field): array
    {
        return [
            'id'     => $field['FIELD_ID'],
            'type'   => 'list',
            'name'   => $field['NAME'],
            'items'  => self::getDeliveryLoadingTypeRef(),
            'params' => ['multiple' => true],
        ];
    }

    /**
     * @param  int|null  $transportTypeId
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private static function getDeliveryLoadingTypeRef(?int $transportTypeId = null): array
    {
        $query = DeliveryLoadingTypeRefPropertyValuesTable::query()
            ->setSelect(['ID' => 'IBLOCK_ELEMENT_ID', 'NAME' => 'ELEMENT.NAME'])
            ->setOrder(['NAME' => 'ASC']);
        if ($transportTypeId > 0) {
            $query->where('TRANSPORT_TYPE_ID', $transportTypeId);
        }
        $dbResult = $query->exec();
        while ($row = $dbResult->fetch()) {
            $deliveryLoadingTypeRef[$row['ID']] = $row;
        }

        return $deliveryLoadingTypeRef ?? [];
    }
}
