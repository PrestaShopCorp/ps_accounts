<?php

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ps_AccountsApiV1ShopLinkAccountModuleFrontController extends AbstractShopRestController
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Parser
     */
    private $jwtParser;

    /**
     * ps_AccountsApiV1ShopAccountModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);

        $this->jwtParser = new Parser();
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function update($shop, array $payload)
    {
        // TODO : store BOTH user JWT & shop JWT
        // TODO : store PS_ACCOUNTS_FIREBASE_USER_ID_TOKEN_[user_id]
        // TODO : API doc

//        TODO RequestValidator/DTO
//        $payload = [
//            'shop_token' => ,
//            'shop_refresh_token' => ,
//            'user_token' => ,
//            'user_refresh_token' => ,
//        ];

        $shopToken = $payload['shop_token'];
        $this->assertValidFirebaseToken($shopToken);

        $userToken = $payload['user_token'];
        $this->assertValidFirebaseToken($userToken);

        $uuid = $this->jwtParser->parse((string) $shopToken)->getClaim('user_id');
        $this->configuration->updateShopUuid($uuid);

        $email = $this->jwtParser->parse((string) $userToken)->getClaim('email');
        $this->configuration->updateFirebaseEmail($email);

        $this->configuration->updateFirebaseIdAndRefreshTokens(
            $payload['shop_token'],
            $payload['shop_refresh_token']
        );

        return [
            'success' => true,
            'message' => 'Link Account stored successfully',
        ];
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function show($shop, array $payload)
    {
        return [
            'shop_token' => $this->configuration->getFirebaseIdToken(),
            'shop_refresh_token' => $this->configuration->getFirebaseRefreshToken(),
            // FIXME : store user tokens
            'user_token' => null,
            'user_refresh_token' => null,

            'shop_uuid' => $this->configuration->getShopUuid(),
            'user_email' => $this->configuration->getFirebaseEmail(),
        ];
    }

    /**
     * @param string $token
     *
     * @return void
     *
     * @throws \Exception
     */
    private function assertValidFirebaseToken($token)
    {
        // TODO: implement verifyFirebaseToken
        //$this->firebaseClient->verifyToken();
    }
}
