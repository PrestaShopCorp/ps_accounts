<?php

namespace PrestaShop\Module\PsAccounts\Repository;

interface PaginatedApiRepositoryInterface
{
    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getFormattedData($offset, $limit);

    /**
     * @param int $offset
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset);
}
