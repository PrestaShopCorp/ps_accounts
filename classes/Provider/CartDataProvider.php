<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\CartProductRepository;
use PrestaShop\Module\PsAccounts\Repository\CartRepository;

class CartDataProvider implements PaginatedApiDataProviderInterface
{
    /**
     * @var CartRepository
     */
    private $cartRepository;
    /**
     * @var CartProductRepository
     */
    private $cartProductRepository;
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    /**
     * @param CartRepository $cartRepository
     * @param CartProductRepository $cartProductRepository
     * @param ArrayFormatter $arrayFormatter
     */
    public function __construct(
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        ArrayFormatter $arrayFormatter
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartProductRepository = $cartProductRepository;
        $this->arrayFormatter = $arrayFormatter;
    }

    public function getFormattedData($offset, $limit, $langIso = null)
    {
        $carts = $this->cartRepository->getCarts($offset, $limit);

        if (!is_array($carts)) {
            return [];
        }

        $cartIds = array_map(function ($cart) {
            return (int) $cart['id_cart'];
        }, $carts);

        $this->castCartValues($carts);

        $carts = array_map(function ($cart) {
            return [
                'id' => $cart['id_cart'],
                'collection' => 'carts',
                'properties' => $cart,
            ];
        }, $carts);

        $cartProducts = $this->cartProductRepository->getCartProducts($cartIds);

        $this->castCartProductValues($cartProducts);

        if (is_array($cartProducts)) {
            $cartProducts = array_map(function ($cartProduct) {
                return [
                    'id' => "{$cartProduct['id_cart']}-{$cartProduct['id_product']}-{$cartProduct['id_product_attribute']}",
                    'collection' => 'cart_products',
                    'properties' => $cartProduct,
                ];
            }, $cartProducts);
        } else {
            $cartProducts = [];
        }

        return array_merge($carts, $cartProducts);
    }

    /**
     * @param int $offset
     * @param string|null $langIso
     *
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso = null)
    {
        return (int) $this->cartRepository->getRemainingCartsCount($offset);
    }

    /**
     * @param array $carts
     *
     * @return void
     */
    private function castCartValues(array &$carts)
    {
        foreach ($carts as &$cart) {
            $cart['id_cart'] = (int) $cart['id_cart'];
        }
    }

    /**
     * @param array $cartProducts
     *
     * @return void
     */
    private function castCartProductValues(array &$cartProducts)
    {
        foreach ($cartProducts as &$cartProduct) {
            $cartProduct['id_cart'] = (int) $cartProduct['id_cart'];
            $cartProduct['id_product'] = (int) $cartProduct['id_product'];
            $cartProduct['id_product_attribute'] = (int) $cartProduct['id_product_attribute'];
            $cartProduct['quantity'] = (int) $cartProduct['quantity'];
        }
    }
}
