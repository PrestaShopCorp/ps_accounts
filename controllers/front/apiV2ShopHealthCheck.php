<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Api\Controller\Request\ShopHealthCheckRequest;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class ps_AccountsApiV2ShopHealthCheckModuleFrontController extends AbstractShopRestController
{
    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var Firebase\ShopSession
     */
    private $firebaseShopSession;

    /**
     * @var Firebase\OwnerSession
     */
    private $firebaseOwnerSession;

    /**
     * @var PsAccountsService
     */
    private $psAccountsService;

    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    public function __construct()
    {
        parent::__construct();

        // public healthcheck
        $this->authenticated = false;

        $this->linkShop = $this->module->getService(LinkShop::class);
        $this->oauth2Client = $this->module->getService(Oauth2Client::class);
        $this->shopSession = $this->module->getService(ShopSession::class);
        $this->firebaseShopSession = $this->module->getService(Firebase\ShopSession::class);
        $this->firebaseOwnerSession = $this->module->getService(Firebase\OwnerSession::class);
        $this->accountsClient = $this->module->getService(AccountsClient::class);
        $this->psAccountsService = $this->module->getService(PsAccountsService::class);
        $this->shopProvider = $this->module->getService(ShopProvider::class);
    }

    /**
     * ?fc=module&module=ps_accounts&controller=apiV1ShopHealthCheck&shop_id=1&autoheal
     *
     * @param Shop $shop
     * @param ShopHealthCheckRequest $request
     *
     * @return array
     */
    public function show(Shop $shop, ShopHealthCheckRequest $request)
    {
        if ($request->autoheal) {
            try {
                $this->firebaseShopSession->getValidToken();
                $this->firebaseOwnerSession->getValidToken();
            } catch (RefreshTokenException $e) {
            }
        }

        $firebaseShopToken = $this->firebaseShopSession->getToken();
        $firebaseOwnerToken = $this->firebaseOwnerSession->getToken();
        $shopToken = $this->shopSession->getToken();

//        $privateInfo = [
//            'shopId' => $shop->id,
//            'shopBoUri' => '',
//            'moduleVersion' => Ps_accounts::VERSION,
//            'psVersion' => _PS_VERSION_,
//            'phpVersion' => phpversion(),
//        ];

        return [
            'oauth2Client' => $this->oauth2Client->exists(),
            'shopLinked' => (bool) $this->linkShop->getShopUuid(),
            'isSsoEnabled' => $this->psAccountsService->getLoginActivated(),
            'oauthToken' => $this->tokenInfos($shopToken),
            'firebaseOwnerToken' => $this->tokenInfos($firebaseOwnerToken),
            'firebaseShopToken' => $this->tokenInfos($firebaseShopToken),
            'fopenActive' => (bool) ini_get('allow_url_fopen'),
            'curlActive' => extension_loaded('curl'), //function_exists('curl_version'),
            'oauthApiConnectivity' => (bool) $this->shopProvider->getWellKnown()->issuer,
            'accountsApiConnectivity' => $this->accountsApiHealthCheck(),
            'serverUTC' => time(),
            'mysqlUTC' => $this->getDatabaseTimestamp(),
            'env' => [
                'oauth2Url' => $this->module->getParameter('ps_accounts.oauth2_url'),
                'accountsApiUrl' => $this->module->getParameter('ps_accounts.accounts_api_url'),
                'accountsUiUrl' => $this->module->getParameter('ps_accounts.accounts_ui_url'),
                'accountsCdnUrl' => $this->module->getParameter('ps_accounts.accounts_cdn_url'),
                'testimonialsUrl' => $this->module->getParameter('ps_accounts.testimonials_url'),
                'checkApiSslCert' => $this->module->getParameter('ps_accounts.check_api_ssl_cert'),
            ],
        ];
    }

    /**
     * {
     * "aud": ["shop_58d..."],
     * "client_id": "374c21dd-8b34-...",
     * "exp": {
     *  "date": "2024-06-11 17:53:26.000000",
     *  "timezone_type": 1,
     *  "timezone": "+00:00"
     * },
     * "ext": {
     * },
     * "iat": {
     *  "date": "2024-06-11 16:53:26.000000",
     *  "timezone_type": 1,
     *  "timezone": "+00:00"
     * },
     * "iss": "https://oauth.prestashop.com",
     * "jti": "bd21ec1d-bc08-458a-bc2b-c29b7cc5abcd",
     * "nbf": {
     *  "date": "2024-06-11 16:53:26.000000",
     *  "timezone_type": 1,
     *  "timezone": "+00:00"
     * },
     * "scp": [],
     * "sub": "374c21dd-8b34-..."
     * }
     *
     * @param Token $token
     *
     * @return array
     */
    private function tokenInfos(Token $token)
    {
        $jwt = $token->getJwt();
        if ($jwt instanceof NullToken) {
            return [];
        }

        $claims = $jwt->claims();

        /** @var DateTimeImmutable $iat */
        $iat = $claims->get('iat');

        /** @var DateTimeImmutable $exp */
        $exp = $claims->get('exp');

        return [
            'issuer' => $claims->get('iss'),
            'issuedAt' => $iat->getTimestamp(),
            'expireAt' => $exp->getTimestamp(),
            'isExpired' => $token->isExpired(),
        ];
    }

    /**
     * @return bool
     */
    private function accountsApiHealthCheck()
    {
        $response = $this->accountsClient->healthCheck();

        return (bool) $response['status'];
    }

    /**
     * @return int
     */
    private function getDatabaseTimestamp()
    {
        try {
            $row = \Db::getInstance()->getRow('SELECT NOW() AS utc');
            if (is_array($row) && isset($row['utc'])) {
                return (new DateTime($row['utc']))->getTimestamp();
            }
        } catch (Exception $e) {
        }

        return 0;
    }
}
