<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Exception\Http\NotFoundException;

class ps_AccountsApiV1ShopUrlModuleFrontController extends AbstractRestController
{
    /**
     * @var string
     */
    public $resourceId = 'shop_id';

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array
     */
    public function show($id, array $payload)
    {
        $shop = new Shop($id);

        if (! $shop->id) {
            throw new NotFoundException('Shop not found [' . $id . ']');
        }

        return [
            'domain' => $shop->domain,
            'domain_ssl' => $shop->domain_ssl,
        ];
    }
}
