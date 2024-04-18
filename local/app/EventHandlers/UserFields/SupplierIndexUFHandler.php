<?php

namespace EventHandlers\UserFields;

use Repositories\SupplierIndexesRefRepository;

class SupplierIndexUFHandler
{
    /**
     * @param $field
     * @param $form
     * @param $html
     */
    public static function onAfterGetPublicView($field, $form, &$html): void
    {
        if ($field['FIELD_NAME'] !== 'UF_SUPPLIER_INDEX_ID' || $form['CONTEXT'] !== 'UI_EDITOR') {
            return;
        }

        self::handleHtml( $html, SupplierIndexesRefRepository::all('ID', [], ['DETAIL_TEXT']), $field['VALUE']);
    }

    /**
     * @param $html
     * @param array $supplierIndexesRef
     * @param $fieldValue
     */
    private static function handleHtml(&$html, array $supplierIndexesRef, $fieldValue): void
    {
        # добавим описание элементов
        $html =
            '<div class="ui-entity-editor-content-block-text">'
            .str_replace(
                $supplierIndexesRef[$fieldValue]['NAME'],
                $supplierIndexesRef[$fieldValue]['NAME'].' '.$supplierIndexesRef[$fieldValue]['DETAIL_TEXT'],
                $html
            )
            .'</div>';
    }
}
