<?php

namespace EventHandlers\Components;

use Bitrix\Main\Event;
use CBitrixComponent;

class OnBeforeIncludeComponentTemplateEventDispatcher
{
    const DEFAULT_TEMPLATE = '.default';

    const COMPONENT_TEMPLATE_HANDLER_CLASSES = [
        'bitrix:lists.list' => [
            self::DEFAULT_TEMPLATE => ListsListBeforeIncludeComponentDefaultTemplateEventHandler::class
        ],
        'bitrix:crm.entity.details.frame' => [
            '' => CrmEntityDetailsFrameBeforeIncludeComponentTemplateHandler::class
        ],
        'bitrix:lists.lists' => [
            self::DEFAULT_TEMPLATE => ListsListsBeforeIncludeComponentDefaultTemplateEventHandler::class
        ]
    ];

    private static array $handlers = [];

    /**
     * @param  Event  $event
     */
    final public static function dispatch(Event $event): void
    {
        /** @var CBitrixComponent $component */
        $component = $event->getParameter('component');
        $handlerClass = self::COMPONENT_TEMPLATE_HANDLER_CLASSES[$component->getName()][$component->getTemplateName()] ?? null;

        if (empty($handlerClass)) {
            return;
        }

        if (empty(static::$handlers[$handlerClass])) {
            static::$handlers[$handlerClass] = new $handlerClass;
        }

        static::$handlers[$handlerClass]->handle($component);
    }
}
