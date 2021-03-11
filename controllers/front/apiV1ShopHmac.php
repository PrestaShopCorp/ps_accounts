<?php

use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

class ps_AccountsApiV1ShopHmacModuleFrontController extends AbstractRestController
{
    /**
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function store(array $payload)
    {
        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        $shopLinkAccountService->writeHmac(
            $payload['hmac'],
            // FIXME : extraire l'id de la shop en cours
            $payload['id'],
            _PS_ROOT_DIR_ . '/upload/'
        );

        return [
            'success' => true,
            'message' => 'HMAC stored successfully',
            'url' => '/upload/' . $payload['id'] . '.txt',
        ];
    }
}
