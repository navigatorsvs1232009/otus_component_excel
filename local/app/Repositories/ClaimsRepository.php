<?php

namespace Repositories;

abstract class ClaimsRepository extends AbstractIblockRepository
{
    const CACHE_DIR = '/claims_ref';
    const IBLOCK_ID = CLAIMS_IBLOCK_ID;
}
