<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use Context;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\OrderDetailsRepository;
use PrestaShop\Module\PsAccounts\Repository\OrderRepository;
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
     * @param string $langIso
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getFormattedData($offset, $limit, $langIso)
    {
        $orders = $this->orderRepository->getOrders($offset, $limit, $this->context->shop->id);

        if (!is_array($orders)) {
            return [];
        }

        $orderIds = $this->arrayFormatter->formatValueArray($orders, 'id_order');

        $orderDetails = $this->getOrderDetails($orderIds);

        $this->castOrderValues($orders);
        $this->castOrderDetailValues($orderDetails);

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
     * @param string $langIso
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso)
    {
        return $this->orderRepository->getRemainingOrderCount($offset, $this->context->shop->id);
    }

    /**
     * @param array $orderIds
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
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

    /**
     * @param array $orders
     *
     * @return void
     */
    public function castOrderValues(array &$orders)
    {
        foreach ($orders as &$order) {
            $order['id_order'] = (int) $order['id_order'];
            $order['id_customer'] = (int) $order['id_customer'];
            $order['id_cart'] = (int) $order['id_cart'];
            $order['current_state'] = (int) $order['current_state'];
            $order['conversion_rate'] = (float) $order['conversion_rate'];
            $order['total_paid_tax_incl'] = (float) $order['total_paid_tax_incl'];
            $order['new_customer'] = $order['new_customer'] === '1';
        }
    }

    /**
     * @param array $orderDetails
     *
     * @return void
     */
    private function castOrderDetailValues(array &$orderDetails)
    {
        foreach ($orderDetails as &$orderDetail) {
            $orderDetail['id_order_detail'] = (int) $orderDetail['id_order_detail'];
            $orderDetail['id_order'] = (int) $orderDetail['id_order'];
            $orderDetail['product_id'] = (int) $orderDetail['product_id'];
            $orderDetail['product_attribute_id'] = (int) $orderDetail['product_attribute_id'];
            $orderDetail['product_quantity'] = (int) $orderDetail['product_quantity'];
            $orderDetail['unit_price_tax_incl'] = (float) $orderDetail['unit_price_tax_incl'];
        }
    }

    public function getFormattedDataIncremental($limit, $langIso = null)
    {
        return [];
    }
}
