<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use Context;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\OrderDetailsRepository;
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
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;
    /**
     * @var OrderDetailsRepository
     */
    private $orderDetailsRepository;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        OrderDetailsRepository $orderDetailsRepository,
        ArrayFormatter $arrayFormatter
    ) {
        $this->orderRepository = $orderRepository;
        $this->context = $context;
        $this->arrayFormatter = $arrayFormatter;
        $this->orderDetailsRepository = $orderDetailsRepository;
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

        $orderIds = $this->arrayFormatter->formatValueArray($orders, 'id_order');

        $orderDetails = $this->getOrderDetails($orderIds);

        $orders = array_map(function ($order) {
            return [
                'id' => $order['id_order'],
                'collection' => 'orders',
                'properties' => $order,
            ];
        }, $orders);

        return array_merge($orders, $orderDetails);
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

    /**
     * @param array $orderIds
     *
     * @return array
     */
    private function getOrderDetails(array $orderIds)
    {
        if (empty($orderIds)) {
            return [];
        }

        $orderDetails = $this->orderDetailsRepository->getOrderDetails($orderIds);

        if (!is_array($orderDetails)) {
            return [];
        }

        return array_map(function ($orderDetail) {
            return [
                'id' => $orderDetail['id_order_detail'],
                'collection' => 'order_details',
                'properties' => $orderDetail,
            ];
        }, $orderDetails);
    }
}
