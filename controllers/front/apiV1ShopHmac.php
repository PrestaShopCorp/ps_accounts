<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class ps_AccountsApiV1ShopHmacModuleFrontController extends AbstractRestController
{
    /**
     * @var string
     */
    public $resourceId = 'shop_id';

    /**
     * @param mixed $id
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function update($id, array $payload)
    {
        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);
        $conf->setShopId($id);

        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        $shopLinkAccountService->writeHmac(
            $payload['hmac'],
            $id, //$this->context->shop->id,
            _PS_ROOT_DIR_ . '/upload/'
        );

        return [
            'success' => true,
            'message' => 'HMAC stored successfully',
        ];
    }
}
