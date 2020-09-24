<?php

namespace PrestaShop\Module\PsAccounts\Repository;

use PrestaShopDatabaseException;

interface PaginatedApiDataProviderInterface
{
    /**
     * @param int $offset
     * @param int $limit
     * @param string $langIso
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit, $langIso = null);

    /**
     * @param int $offset
     * @param string $langIso
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     */
    public function getRemainingObjectsCount($offset, $langIso = null);
}
