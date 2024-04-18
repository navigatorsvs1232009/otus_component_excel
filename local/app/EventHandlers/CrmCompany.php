<?php

namespace EventHandlers;

use Bitrix\Main\Application;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Models\SCM\SupplierRatingTable;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\SCM\CompanyDriveFolderHandler;
use Services\Infrastructure\SCM\CompanyQaTraceCodeGenerator;
use Throwable;
use Services\Infrastructure\SCM\SupplierRatingValuesHandler;

abstract class CrmCompany
{
    /**
     * @param  array $company
     *
     * @return bool
     */

    public static function onBeforeUpdate(array $company): bool
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $supplierRatingValues = json_decode($request->get('SUPPLIER_RATING_VALUES'), true) ?: [];

        if (sizeof($supplierRatingValues) != sizeof(array_filter($supplierRatingValues))) {
            $GLOBALS['fields']['RESULT_MESSAGE'] = 'All supplier rating fields must be filled.';
            return false;
        }

        return true;
    }

    /**
     * @param  array  $company
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterUpdate(array $company): void
    {
        $request = Application::getInstance()->getContext()->getRequest();

        if (isset($company['TITLE'])
            || isset($company['UF_SHORT_TITLE'])
            || isset($company['UF_COUNTRY'])
            || isset($company['COMPANY_TYPE'])
        ) {
            try {
                (new CompanyDriveFolderHandler())->handle($company['ID']);
            } catch (Throwable $e) {
                //
            }
        }

        $supplierRatingValues = $request->get('SUPPLIER_RATING_VALUES');
        if (isset($supplierRatingValues)) {
            if (SupplierRatingTable::getCount(['COMPANY_ID' => $company['ID']]) > 0) {
                SupplierRatingTable::update($company['ID'], ['RATING_VALUES' => $supplierRatingValues]);

            } else {
                SupplierRatingTable::add([
                    'COMPANY_ID'    => $company['ID'],
                    'RATING_VALUES' => $supplierRatingValues,
                ]);
            }
        }

        Application::getInstance()->addBackgroundJob([(new SupplierRatingValuesHandler()), 'handle'], [$company['ID']]);

        EntityChangesLoggingService::run('company_update', $company['ID']);
    }

    /**
     * @param  array  $company
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterAdd(array $company): void
    {
        (new CompanyQaTraceCodeGenerator())->run($company['ID']);

        self::onAfterUpdate($company);
    }
}
