<?php

namespace Repositories;

class BankTransactionsRepository extends AbstractIblockRepository
{
    const CACHE_DIR = '/bank_transactions_ref';
    const IBLOCK_ID = BANK_TRANSACTIONS_IBLOCK_ID;
}