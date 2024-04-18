<?php

namespace Traits\Components;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;

Trait PageNavigationTrait
{
    /**
     * @param  GridOptions  $gridOptions
     * @param  array  $filter
     *
     * @return PageNavigation
     */
    private function getPageNavigation(GridOptions $gridOptions, array $filter): PageNavigation
    {
        $pageNavigation = new PageNavigation($gridOptions->getId());
        $navParams = $gridOptions->GetNavParams();
        $pageNavigation->setPageSize($navParams['nPageSize'] ?? static::DEFAULT_PAGE_SIZE);
        $pageNavigation->setRecordCount($this->getRows($filter, [])['count']);
        if ($this->request->offsetExists('page')) {
            $currentPage = $this->request->get('page');
            $pageNavigation->setCurrentPage($currentPage > 0 ? $currentPage : $pageNavigation->getPageCount());
        } else {
            $pageNavigation->setCurrentPage(1);
        }

        return $pageNavigation;
    }
}
