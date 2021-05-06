<?php

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use PrestaShop\Module\PsAccounts\Service\ShopTokenService;
use PrestaShop\Module\PsAccounts\Service\SsoService;

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
     * @var SsoService
     */
    private $ssoService;

    /**
     * @var ShopTokenService
     */
    private $shopTokenService;

    /**
     * @var ShopLinkAccountService
     */
    private $shopLinkAccountService;

    /**
     * ps_AccountsApiV1ShopAccountModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
        $this->ssoService = $this->module->getService(SsoService::class);
        $this->shopTokenService = $this->module->getService(ShopTokenService::class);
        $this->shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        $this->jwtParser = new Parser();
    }

    /**
     * Expected Payload keys :
     *  - shop_token
     *  - shop_refresh_token
     *  - user_token
     *  - user_refresh_token
     *  - employee_id
     *
     * @param Shop $shop
     * @param array $payload
     *
     * @return array|void
     *
     * @throws Exception
     */
    public function update($shop, array $payload)
    {
        $shopRefreshToken = $payload['shop_refresh-token'];
        $shopToken = $this->shopTokenService->verifyToken($payload['shop_token'], $shopRefreshToken);

        $userRefreshToken = $payload['user_refresh_token'];
        $userToken = $this->ssoService->verifyToken($payload['user_token'], $userRefreshToken);

        $this->configuration->updateShopFirebaseCredentials($shopToken, $shopRefreshToken);
        $this->configuration->updateUserFirebaseCredentials($userToken, $userRefreshToken);
        $this->configuration->updateEmployeeId($payload['employee_id']);

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
     */
    public function delete($shop, array $payload)
    {
        $this->shopLinkAccountService->resetOnboardingData();

        return [
            'success' => true,
            'message' => 'Link Account deleted successfully',
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
        list($userIdToken, $userRefreshToken) = $this->configuration->getUserFirebaseCredentials();

        return [
            'shop_token' => $this->configuration->getFirebaseIdToken(),
            'shop_refresh_token' => $this->configuration->getFirebaseRefreshToken(),
            'user_token' => $userIdToken,
            'user_refresh_token' => $userRefreshToken,
            'employee_id' => $this->configuration->getEmployeeId(),
        ];
    }
}
