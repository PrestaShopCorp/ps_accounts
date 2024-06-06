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

namespace PrestaShop\Module\PsAccounts\Account\Session;

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Hook\ActionShopAccessTokenRefreshAfter;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Grant\ClientCredentials;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessTokenInterface;

class ShopSession extends Session implements SessionInterface
{
    /**
     * @var ShopProvider
     */
    protected $oauth2ClientProvider;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param ShopProvider $oauth2ClientProvider
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        ShopProvider $oauth2ClientProvider
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->oauth2ClientProvider = $oauth2ClientProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        $token = parent::getOrRefreshToken($forceRefresh);

        \Hook::exec(ActionShopAccessTokenRefreshAfter::getName(), ['token' => $token]);

        return $token;
    }

    /**
     * @param string $refreshToken
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function refreshToken($refreshToken = null)
    {
        try {
            $shopUuid = $this->getShopUuid();
            $accessToken = $this->getAccessToken($shopUuid);

            //return new Token($accessToken->getToken(), $accessToken->getRefreshToken());
            $this->setToken(
                $accessToken->getToken(),
                $accessToken->getRefreshToken()
            );

            return $this->getToken();
        } catch (IdentityProviderException $e) {
        } catch (\Error $e) {
        } catch (\Exception $e) {
        }
        throw new RefreshTokenException('Unable to refresh shop token : ' . $e->getMessage());
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return new Token($this->configurationRepository->getAccessToken());
    }

    /**
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken($token, $refreshToken = null)
    {
        $this->configurationRepository->updateAccessToken($token);
    }

    /**
     * @return void
     */
    public function cleanup()
    {
        $this->configurationRepository->updateAccessToken('');
    }

    /**
     * @param string $shopUid
     *
     * @return AccessToken|AccessTokenInterface
     *
     * @throws IdentityProviderException
     * @throws \Exception
     */
    protected function getAccessToken($shopUid)
    {
        $audience = [
            'shop_' . $shopUid,
            //'another.audience'
        ];
        /**
         * /!\ Potential scoping issue here :
         *
         * using 'client_credentials' as a string literal alternative for grant will trigger the following error with PPHUnit context,
         * so better avoid it in case it triggers an error elsewhere :
         *
         * PHP Fatal error:  Cannot declare class PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Grant\ClientCredentials,
         * because the name is already in use in
         * /var/www/html/modules/ps_accounts/vendor/league/oauth2-client/src/Grant/ClientCredentials.php on line 22
         */
        $token = $this->oauth2ClientProvider->getAccessToken(new ClientCredentials(), /*'client_credentials',*/ [
            //'scope' => 'read.all write.all',
            'audience' => implode(' ', $audience),
        ]);
        Logger::getInstance()->debug(__METHOD__ . json_encode($token->jsonSerialize(), JSON_PRETTY_PRINT));

        return $token;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    private function getShopUuid()
    {
        /** @var \Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var LinkShop $linkShop */
        $linkShop = $module->getService(LinkShop::class);

        return $linkShop->getShopUuid();
    }
}
