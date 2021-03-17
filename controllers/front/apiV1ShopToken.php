<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\ShopTokenService;

class ps_AccountsApiV1ShopTokenModuleFrontController extends AbstractRestController
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
     *
     * @throws Exception
     */
    public function show($id, array $payload)
    {
        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);
        $conf->setShopId($id);

        /** @var ShopTokenService $shopTokenService */
        $shopTokenService = $this->module->getService(ShopTokenService::class);

        return [
            'token' => $shopTokenService->getOrRefreshToken(),
            'refresh_token' => $shopTokenService->getRefreshToken(),
        ];
    }
}
