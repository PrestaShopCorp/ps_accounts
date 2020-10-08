<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use Context;
use PrestaShop\Module\PsAccounts\Repository\OrderRepository;
use PrestaShop\Module\PsAccounts\Repository\PaginatedApiDataProviderInterface;
use PrestaShopDatabaseException;

class OrderDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var Context
     */
    private $context;

    public function __construct(OrderRepository $orderRepository, Context $context)
    {
        $this->orderRepository = $orderRepository;
        $this->context = $context;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param null $langIso
     *
     * @return array|array[]
     *
     * @throws PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit, $langIso = null)
    {
        $orders = $this->orderRepository->getOrders($offset, $limit, $this->context->shop->id);

        return array_map(function ($order) {
            return [
                'id' => $order['id_order'],
                'collection' => 'orders',
                'properties' => $order,
            ];
        }, $orders);
    }

    /**
     * @param int $offset
     * @param null $langIso
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso = null)
    {
        return $this->orderRepository->getRemainingOrderCount($offset, $this->context->shop->id);
    }
}
