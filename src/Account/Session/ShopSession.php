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
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessToken;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Token\AccessTokenInterface;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;

class ShopSession extends Session implements SessionInterface
{
    use RefreshFirebaseTokens;

    /**
     * @var PrestaShop
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
     * @param bool $forceRefresh
     * @param bool $refreshFirebaseTokens
     *
     * @return Token
     *
     * @throws \Exception
     */
    public function getOrRefreshToken($forceRefresh = false, $refreshFirebaseTokens = false)
    {
        $token = parent::getOrRefreshToken($forceRefresh);

        try {
            if ($refreshFirebaseTokens) {
                $this->refreshFirebaseTokens($token);
            }

            \Hook::exec(ActionShopAccessTokenRefreshAfter::getName(), ['token' => $token]);
        } catch (\Error $e) {
        } catch (\Exception $e) {
        }
        if (isset($e)) {
            Logger::getInstance()->error('Unable to get or refresh shop token : ' . $e->getMessage());
        }

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

            return new Token($accessToken->getToken(), $accessToken->getRefreshToken());
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
            // 'another.audience'
        ];
        $token = $this->oauth2ClientProvider->getAccessToken('client_credentials', [
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
