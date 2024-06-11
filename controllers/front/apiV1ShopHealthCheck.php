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
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;

class ps_AccountsApiV1ShopHealthCheckModuleFrontController extends AbstractShopRestController
{
    /**
     * @var bool
     */
    protected $authenticated = false;

    /**
     * ?fc=module&module=ps_accounts&controller=apiV1ShopHealthCheck&shop_id=1
     *
     * @param Shop $shop
     * @param array $payload
     * @return array
     *
     * @throws Exception
     */
    public function show(Shop $shop, array $payload)
    {
        /** @var LinkShop $linkShop */
        $linkShop = $this->module->getService(LinkShop::class);

        /** @var Oauth2Client $oauth2Client */
        $oauth2Client = $this->module->getService(Oauth2Client::class);

        /** @var ShopSession $shopSession */
        $shopSession = $this->module->getService(ShopSession::class);

        /** @var Firebase\ShopSession $shopFirebaseSession */
        $firebaseShopSession = $this->module->getService(Firebase\ShopSession::class);

        /** @var Firebase\OwnerSession $shopFirebaseSession */
        $firebaseOwnerSession = $this->module->getService(Firebase\OwnerSession::class);

        // FIXME: non authenticated route
        // FIXME: specify shop id

        return [
            'module_version' => Ps_accounts::VERSION,
            'ps_version' => _PS_VERSION_,
            'php_version' => '',
            'oauth2_client' => $oauth2Client->exists(),
            'allow_url_fopen' => (bool) ini_get('allow_url_fopen'),
            'link_status' => (bool) $linkShop->getShopUuid(),
            'tokens' => [
                'access_token' => ! ($shopSession->getToken()->getJwt() instanceof NullToken) &&
                    ! $shopSession->getToken()->isExpired(),
                'firebase_shop_token' => ! ($firebaseShopSession->getToken()->getJwt() instanceof NullToken) &&
                    ! $firebaseShopSession->getToken()->isExpired(),
                'firebase_owner_token' => ! ($firebaseOwnerSession->getToken()->getJwt() instanceof NullToken) &&
                    ! $firebaseOwnerSession->getToken()->isExpired(),
            ],
        ];
    }
}
