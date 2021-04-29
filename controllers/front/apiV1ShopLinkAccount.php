<?php

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Api\Client\SsoClient;
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
     *
     * @throws Exception
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
//            'employee_id' => ,
//        ];

        $shopToken = $payload['shop_token'];
        $this->verifyShopToken($shopToken);

        $userToken = $payload['user_token'];
        $this->verifyUserToken($userToken);

        $uuid = $this->jwtParser->parse((string) $shopToken)->getClaim('user_id');
        $this->configuration->updateShopUuid($uuid);

        $email = $this->jwtParser->parse((string) $userToken)->getClaim('email');
        $this->configuration->updateFirebaseEmail($email);

        // TODO: store customerId
        //$employeeId = $payload['employee_id'];
        //$this->configuration->updateEmployeeId($employeeId);

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
     * @param $shopToken
     *
     * @throws Exception
     */
    private function verifyShopToken($shopToken)
    {
        // TODO : attempt refresh token
        // TODO : return right HttpException

        /** @var ServicesAccountsClient $accountsApiClient */
        $accountsApiClient = $this->module->getService(ServicesAccountsClient::class);
        $response = $accountsApiClient->verifyToken($shopToken);

        if (true !== $response['status']) {
            throw new \Exception('Unable to verify shop token : ' . $response['httpCode'] . ' ' . $response['body']['message']);
        }
    }

    /**
     * @param $userToken
     *
     * @throws Exception
     */
    private function verifyUserToken($userToken)
    {
        // TODO : attempt refresh token
        // TODO : return right HttpException

        /** @var SsoClient $ssoApiClient */
        $ssoApiClient = $this->module->getService(SsoClient::class);
        $response = $ssoApiClient->verifyToken($userToken);

        if (true !== $response['status']) {
            throw new \Exception('Unable to verify user token : ' . $response['httpCode'] . ' ' . $response['body']['message']);
        }
    }
}
