<?php

namespace EventHandlers\SCM;

use Controllers\Rest\SCM\BankTransactionController;
use Controllers\Rest\SCM\CompanyController;
use Controllers\Rest\SCM\DemandController;
use Controllers\Rest\SCM\FinalPriceController;
use Controllers\Rest\SCM\ProductionApprovalController;
use Controllers\Rest\SCM\ProductsTransitController;
use Controllers\Rest\SCM\PurchaseOrderController;
use Controllers\Rest\SCM\RfqController;
use Controllers\Rest\SCM\RPriceController;
use Services\Rest\SCM\PaymentUpdateRestService;

class RestServiceHandler
{
    /**
     * Регистрация методов/событий
     *
     * @return array
     */
    public static function OnRestServiceBuildDescription(): array
    {
        return [
            'scm' => [
                'scm.purchase_order.create' => [
                    'callback' => [PurchaseOrderController::class, 'create'],
                    'options'  => [],
                ],
                'scm.purchase_order.update' => [
                    'callback' => [PurchaseOrderController::class, 'update'],
                    'options'  => [],
                ],
                'scm.payment.update' => [
                    'callback' => [PaymentUpdateRestService::class, 'run'],
                    'options'  => [],
                ],
                'scm.company.list' => [
                    'callback' => [CompanyController::class, 'list'],
                    'options'  => [],
                ],
                'scm.company.update' => [
                    'callback' => [CompanyController::class, 'update'],
                    'options'  => [],
                ],
                'scm.rfq.import' => [
                    'callback' => [RfqController::class, 'import'],
                    'options'  => [],
                ],
                'scm.rprice.import' => [
                    'callback' => [RPriceController::class, 'import'],
                    'options'  => [],
                ],
                'scm.demand.import' => [
                    'callback' => [DemandController::class, 'import'],
                    'options'  => [],
                ],
                'scm.final_price.import' => [
                    'callback' => [FinalPriceController::class, 'import'],
                    'options'  => [],
                ],
                'scm.production_approval.import' => [
                    'callback' => [ProductionApprovalController::class, 'import'],
                    'options'  => [],
                ],
                'scm.bank_transaction.import' => [
                    'callback' => [BankTransactionController::class, 'import'],
                    'options'  => [],
                ],
                'scm.products_transit.list'   => [
                    'callback' => [ProductsTransitController::class, 'list'],
                    'options'  => [],
                ]
            ],
        ];
    }
}
