<?php

namespace Repositories;

abstract class CountriesRefRepository extends AbstractIblockRepository
{
    const CACHE_DIR = '/countries_ref';
    const IBLOCK_ID = COUNTRIES_REF_IBLOCK_ID;
}
