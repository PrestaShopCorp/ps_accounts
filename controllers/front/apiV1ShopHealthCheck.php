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
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;

class ps_AccountsApiV1ShopHealthCheckModuleFrontController extends AbstractShopRestController
{
    /**
     * @var bool
     */
    protected $authenticated = false;

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

    public function __construct()
    {
        parent::__construct();

        $this->linkShop = $this->module->getService(LinkShop::class);
        $this->oauth2Client = $this->module->getService(Oauth2Client::class);
        $this->shopSession = $this->module->getService(ShopSession::class);
        $this->firebaseShopSession = $this->module->getService(Firebase\ShopSession::class);
        $this->firebaseOwnerSession = $this->module->getService(Firebase\OwnerSession::class);
    }

    /**
     * ?fc=module&module=ps_accounts&controller=apiV1ShopHealthCheck&shop_id=1&autoheal
     *
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function show(Shop $shop, array $payload)
    {
        // refreshing one of firebase tokens will trigger a global refresh
        $firebaseShopToken = isset($payload['autoheal']) ?
            $this->firebaseShopSession->getToken() :
            $this->firebaseShopSession->getOrRefreshToken();
        $firebaseOwnerToken = $this->firebaseOwnerSession->getToken();
        $shopToken = $this->shopSession->getToken();

        return [
            'shopLinked' => (bool) $this->linkShop->getShopUuid(),
            'isSsoEnabled' => null,
            'oauthTokens' => $this->tokenInfos($shopToken),
            'firebaseUserTokens' => $this->tokenInfos($firebaseOwnerToken),
            'firebaseShopTokens' => $this->tokenInfos($firebaseShopToken),
            'fopenActive' => (bool) ini_get('allow_url_fopen'),
            'curlActive' => '', //(bool) ini_get(''),
            'accountsApiConnectivy' => '', // TODO
            'env' => [
                'oauth2Url' => $this->module->getParameter('ps_accounts.oauth2_url'),
                'accountsApiUrl' => $this->module->getParameter('ps_accounts.accounts_api_url'),
                'accountsUiUrl' => $this->module->getParameter('ps_accounts.accounts_ui_url'),
                'accountsCdnUrl' => $this->module->getParameter('ps_accounts.accounts_cdn_url'),
                'testimonialsUrl' => $this->module->getParameter('ps_accounts.testimonials_url'),
                'checkApiSslCert' => $this->module->getParameter('ps_accounts.check_api_ssl_cert'),
            ],
            // FIXME
            'toBeDiscussed' => [
                'module_version' => Ps_accounts::VERSION,
                'ps_version' => _PS_VERSION_,
                'php_version' => '',
                'oauth2_client' => $this->oauth2Client->exists(),
            ],
        ];
    }

    /**
     * {
     * "aud": [
     *  "shop_58d55d88-ee76-4d25-8a34-2bc370abcdef"
     * ],
     * "client_id": "374c21dd-8b34-47fd-82fc-e2264faabcdef",
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
     * "sub": "374c21dd-8b34-47fd-82fc-e2264fabcdef"
     * }
     *
     * @param Token $token
     *
     * @return array
     */
    private function tokenInfos(Token $token)
    {
        $claims = $token->getJwt()->claims();

        return [
            'issuer' => $claims->get('iss'),
            'issuedAt' => $claims->get('iat'),
            'expDate' => $claims->get('exp'),
        ];
    }
}
