<?php

namespace Traits\Components;

use Bitrix\Main\Grid\Options as GridOptions;

Trait SortOrderTrait
{
    /**
     * @param  GridOptions  $gridOptions
     * @param  array  $sortableFields
     *
     * @return array
     */
    private function getSortOrder(GridOptions $gridOptions, array $sortableFields = []): array
    {
        $gridSort = $gridOptions->getSorting();
        $sortableFields = $sortableFields ?: static::SORTABLE_FIELDS;

        return array_filter(
            $gridSort['sort'],
            fn ($field) => in_array($field, $sortableFields),
            ARRAY_FILTER_USE_KEY
        ) ?: ['ID' => 'asc'];
    }
}
