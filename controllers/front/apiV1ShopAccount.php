<?php

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ps_AccountsApiV1ShopAccountModuleFrontController extends AbstractRestController
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
        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $uuid = (new Parser())->parse((string) $payload['shop_token'])->getClaim('user_id');
        $configuration->updateShopUuid($uuid);

        $email = (new Parser())->parse((string) $payload['user_token'])->getClaim('email');
        $configuration->updateFirebaseEmail($email);

        $configuration->updateFirebaseIdAndRefreshTokens(
            $payload['shop_token'],
            $payload['shop_refresh_token']
        );

        return [
            'success' => true,
            'message' => 'Link Account stored successfully',
        ];
    }
}
