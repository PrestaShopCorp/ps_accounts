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

        $this->castOrderValues($orders);

        $orderDetails = $this->getOrderDetails($orders, $this->context->shop->id);

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
        return (int) $this->orderRepository->getRemainingOrderCount($offset, $this->context->shop->id);
    }

    /**
     * @param array $orders
     * @param int $shopId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    private function getOrderDetails(array $orders, $shopId)
    {
        if (empty($orders)) {
            return [];
        }

        $orderIds = $this->arrayFormatter->formatValueArray($orders, 'id_order');

        $orderDetails = $this->orderDetailsRepository->getOrderDetails($orderIds, $shopId);

        if (!is_array($orderDetails) || empty($orderDetails)) {
            return [];
        }

        $this->castOrderDetailValues($orderDetails);

        $orderDetails = array_map(function ($orderDetail) {
            return [
                'id' => $orderDetail['id_order_detail'],
                'collection' => 'order_details',
                'properties' => $orderDetail,
            ];
        }, $orderDetails);

        return $orderDetails;
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
            $order['current_state'] = (int) $order['current_state'];
            $order['conversion_rate'] = (float) $order['conversion_rate'];
            $order['total_paid_tax_incl'] = (float) $order['total_paid_tax_incl'];
            $order['total_paid_tax_excl'] = (float) $order['total_paid_tax_excl'];
            $order['refund'] = (float) $order['refund'];
            $order['refund_tax_excl'] = (float) $order['refund_tax_excl'];
            $order['new_customer'] = $order['new_customer'] === '1';
            $order['is_paid'] = (float) $order['total_paid_real'] >= (float) $order['total_paid_tax_incl'];
            $order['shipping_cost'] = (float) $order['shipping_cost'];
            $order['total_paid_tax'] = $order['total_paid_tax_incl'] - $order['total_paid_tax_excl'];
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
            $orderDetail['unit_price_tax_excl'] = (float) $orderDetail['unit_price_tax_excl'];
            $orderDetail['refund'] = (float) $orderDetail['refund'] > 0 ? -1 * (float) $orderDetail['refund'] : 0;
            $orderDetail['refund_tax_excl'] = (float) $orderDetail['refund_tax_excl'] > 0 ? -1 * (float) $orderDetail['refund_tax_excl'] : 0;
            $orderDetail['category'] = (int) $orderDetail['category'];
            $orderDetail['unique_product_id'] = "{$orderDetail['product_id']}-{$orderDetail['product_attribute_id']}-{$orderDetail['iso_code']}";
        }
    }

    /**
     * @param int $limit
     * @param string $langIso
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getFormattedDataIncremental($limit, $langIso)
    {
        $orders = $this->orderRepository->getOrdersIncremental($limit, $this->context->shop->id);

        if (!is_array($orders) || empty($orders)) {
            return [
                'ids' => [],
                'data' => [],
            ];
        }

        $orderIds = $this->separateOrderIds($orders);

        $this->castOrderValues($orders);

        $orderDetails = $this->getOrderDetails($orders, $this->context->shop->id);

        $orders = array_map(function ($order) {
            return [
                'id' => $order['id_order'],
                'collection' => 'orders',
                'properties' => $order,
            ];
        }, $orders);

        return [
            'data' => array_merge($orders, $orderDetails),
            'ids' => $orderIds,
        ];
    }

    /**
     * @param array $orders
     *
     * @return array
     */
    private function separateOrderIds(array $orders)
    {
        return $this->arrayFormatter->formatValueArray($orders, 'id_order', true);
    }
}
