<?php

namespace Models\SCM;

use Bitrix\Main\Loader;
use CCrmCompany;

Loader::includeModule('crm');

class CompanyTable extends \Bitrix\Crm\CompanyTable
{
    /**
     * @param  int  $companyId
     */
    public static function touch(int $companyId): void
    {
        static $company = null;
        if (is_null($company)) {
            $company = new CCrmCompany();
        }

        $_ = [];
        $company->Update($companyId, $_);
    }
}
