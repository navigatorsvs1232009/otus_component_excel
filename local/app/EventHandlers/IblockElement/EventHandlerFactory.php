<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\Engine\CurrentUser;

/**
 * Class EventHandlerFactory
 *
 * @package EventHandlers\IblockElement
 */
class EventHandlerFactory
{
    /**
     * @param $iblockId
     *
     * @return object|null
     */
    public static function create(int $iblockId): ?object
    {
        $handlerClass = match ($iblockId) {
            SOPO_IBLOCK_ID                      => SopoEventHandler::class,
            ORDERS_IBLOCK_ID                    => OrderEventHandler::class,
            PAYMENTS_IBLOCK_ID                  => PaymentsEventHandler::class,
            COMPANY_DEPENDENCIES_IBLOCK_ID      => DependencyEventHandler::class,
            SUPPLY_CONTRACTS_IBLOCK_ID          => SupplyContractEventHandler::class,
            RFQ_IBLOCK_ID                       => RfqEventHandler::class,
            RFQ_PRODUCT_ROWS_IBLOCK_ID          => RfqProductRowsHandler::class,
            COMPANY_NOMENCLATURE_IBLOCK_ID      => NomenclatureEventHandler::class,
            RPRICE_IBLOCK_ID                    => RPriceEventHandler::class,
            INCOTERMS_PLACE_REF_IBLOCK_ID       => IncotermsPlaceEventHandler::class,
            COMPANY_SUPPLY_AREAS_IBLOCK_ID      => CompanySupplyAreasEventHandler::class,
            EMPLOYEE_RESPONSIBILITIES_IBLOCK_ID => EmployeeResponsibilitiesHandler::class,
            SPECIFICATION_IBLOCK_ID             => SpecificationEventHandler::class,
            ORDER_STAGES_REF_IBLOCK_ID          => OrderStagesRefEventHandler::class,
            BANK_TRANSACTIONS_IBLOCK_ID         => BankTransactionEventHandler::class,
            PRICE_PER_KILO_IBLOCK_ID            => PricePerKiloEventHandler::class,
            COMPANY_CURRENCY_RATES_IBLOCK_ID    => CompanyCurrencyRateEventHandler::class,
            RPRICE_PRODUCT_ROWS_IBLOCK_ID       => RpriceProductRowEventHandler::class,
            PAYMENT_TERMS_IBLOCK_ID             => PaymentTermsEventHandler::class,
            TRANSPORT_TIME_REF_IBLOCK_ID        => TransportTimeEventHandler::class,
            DELIVERY_IBLOCK_ID                  => DeliveryEventHandler::class,
            SUPPLY_AREA_REF_IBLOCK_ID           => SupplyAreaRefEventHandler::class,
            ROUTES_REF_IBLOCK_ID                => RoutesRefEventHandler::class,
            TRANSPORTING_ROUTES_REF_IBLOCK_ID   => TransportingRoutesRefEventHandler::class,
            default                             => null
        };

        $handler = isset($handlerClass) ? new $handlerClass : null;
        if ($handler) {
            $handler->currenUser = CurrentUser::get();
        }

        return $handler;
    }
}
