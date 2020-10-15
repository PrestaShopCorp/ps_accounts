<?php

namespace PrestaShop\Module\PsAccounts\Provider;

use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;
use PrestaShop\Module\PsAccounts\Repository\CartProductRepository;
use PrestaShop\Module\PsAccounts\Repository\CartRepository;
use PrestaShop\Module\PsAccounts\Repository\PaginatedApiDataProviderInterface;

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


        return array_map(function ($cart) {

        }, $carts);
    }

    /**
     * @param int $offset
     * @param string|null $langIso
     * @return int
     */
    public function getRemainingObjectsCount($offset, $langIso = null)
    {
        return (int) $this->cartRepository->getRemainingCartsCount($offset);
    }
}
