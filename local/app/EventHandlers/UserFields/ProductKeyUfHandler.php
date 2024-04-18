<?php

namespace EventHandlers\UserFields;

use Repositories\ProductKeysRef;

class ProductKeyUfHandler
{
    /**
     * @param $field
     * @param $form
     * @param $html
     */
    public static function onAfterGetPublicEdit($field, $form, &$html): void
    {
        if ($field['FIELD_NAME'] !== 'UF_PRODUCT_KEY' || $form['CONTEXT'] !== 'UI_EDITOR') {
            return;
        }

        self::handleHtml($html, ProductKeysRef::all('NAME', [], ['DETAIL_TEXT']));
    }

    /**
     * @param $field
     * @param $form
     * @param $html
     */
    public static function onAfterGetPublicView($field, $form, &$html): void
    {
        if ($field['FIELD_NAME'] !== 'UF_PRODUCT_KEY' || $form['CONTEXT'] !== 'UI_EDITOR') {
            return;
        }

        self::handleHtml($html, ProductKeysRef::all('NAME', [], ['DETAIL_TEXT']));
    }

    /**
     * @param $html
     * @param  array  $productKeyRef
     */
    private static function handleHtml(&$html, array $productKeyRef): void
    {
        # добавим прокрутку и описание элементов
        $html =
            '<div style="max-height: 300px; overflow-y: auto">'
            .str_replace(
                array_column($productKeyRef, 'NAME'),
                array_map(function ($item) {
                    return $item['NAME'].' - '.$item['DETAIL_TEXT'];
                }, $productKeyRef),
                $html
            )
            .'</div>';
    }
}
