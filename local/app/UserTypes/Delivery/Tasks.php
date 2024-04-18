<?php

namespace UserTypes\Delivery;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Repositories\DeliveriesRepository;

class Tasks
{
    const USER_TYPE_ID = 'delivery_tasks';

    public static array $data = [];

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => 'Delivery tasks',
            'PROPERTY_TYPE' => 'E',
            'USER_TYPE'     => self::class,

            'GetPublicViewHTMLMulty'      => [self::class, 'GetPublicViewHTMLMulty'],
            'GetAdminListViewHTML'   => [self::class, 'GetPublicViewHTML'],
            'GetPublicEditHTMLMulty' => [self::class, 'GetPublicEditHTMLMulty'],
            'GetPropertyFieldHtml'   => [self::class, 'GetPublicEditHTML'],
        ];
    }

    /**
     * @param  array  $field
     * @param  ?array  $values
     * @param  array  $HTMLControl
     *
     * @return string
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function GetPublicEditHTMLMulty(array $field, ?array $values, array $HTMLControl = []) : string
    {
        $field['USER_TYPE'] = self::class;
        $tasks = $field['ELEMENT_ID'] > 0 ? DeliveriesRepository::getDeliveryTasks($field['ELEMENT_ID']) : [];
        $orderedProductsTotalWeight = DeliveriesRepository::getOrderedProductsTotalWeight(array_column($tasks, 'UF_PROC_ORDER_ID'));
        foreach ($tasks as &$task) {
            $task['ORDER_WEIGHT'] = $orderedProductsTotalWeight[$task['UF_PROC_ORDER_ID']] ?? 0.0;
        }

        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField' => $field,
                'controlName' => $HTMLControl['VALUE'],
                'tasks'       => $tasks,
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
        return join('<br>', array_map(
                fn($taskId) => "<a href='/company/personal/user/0/tasks/task/view/{$taskId}/' target='_blank'>{$taskId}</a>",
                $field['VALUE'] ?? $values['VALUE'] ?: []
            )
        );
    }
}
