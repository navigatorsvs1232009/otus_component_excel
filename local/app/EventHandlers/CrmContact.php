<?php

namespace EventHandlers;

use Bitrix\Crm\ContactTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\SCM\SupplierRatingValuesHandler;

abstract class CrmContact
{
    /**
     * @param  array  $contact
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterUpdate(array $contact): void
    {
        $contactCompanyId = ContactTable::getByPrimary($contact['ID'], ['select' => ['COMPANY_ID']])->fetch()['COMPANY_ID'];
        if (!empty($contactCompanyId)) {
            (new SupplierRatingValuesHandler())->handle($contactCompanyId);
        }

        EntityChangesLoggingService::run('contact', $contact['ID']);
    }

    /**
     * @param  array  $contact
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterAdd(array $contact): void
    {
        self::onAfterUpdate($contact);
    }
}
