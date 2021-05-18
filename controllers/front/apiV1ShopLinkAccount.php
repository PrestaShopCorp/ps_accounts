<?php

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;

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
     * @var UserTokenRepository
     */
    private $userTokenRepository;

    /**
     * @var ShopTokenRepository
     */
    private $shopTokenRepository;

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
        $this->userTokenRepository = $this->module->getService(UserTokenRepository::class);
        $this->shopTokenRepository = $this->module->getService(ShopTokenRepository::class);
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
        list($shopRefreshToken, $userRefreshToken, $shopToken, $userToken, $employeeId) = [
            $payload['shop_refresh_token'],
            $payload['user_refresh_token'],
            $payload['shop_token'],
            $payload['user_token'],
            // FIXME : temporary fix
            (array_key_exists('employee_id', $payload) ? $payload['employee_id'] : ''),
        ];

        $verifyTokens = $this->module->getParameter('ps_accounts.verify_account_tokens');
        if ($verifyTokens) {
            $shopToken = $this->shopTokenRepository->verifyToken($shopToken, $shopRefreshToken);
            $userToken = $this->userTokenRepository->verifyToken($userToken, $userRefreshToken);
        }

        $this->shopTokenRepository->updateCredentials($shopToken, $shopRefreshToken);
        $this->userTokenRepository->updateCredentials($userToken, $userRefreshToken);
        $this->configuration->updateEmployeeId($employeeId);

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
        $this->shopLinkAccountService->resetLinkAccount();

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
        return [
            'shop_token' => (string) $this->shopTokenRepository->getToken(),
            'shop_refresh_token' => (string) $this->shopTokenRepository->getRefreshToken(),
            'user_token' => (string) $this->userTokenRepository->getToken(),
            'user_refresh_token' => (string) $this->userTokenRepository->getRefreshToken(),
            'employee_id' => $this->configuration->getEmployeeId(),
        ];
    }
}
