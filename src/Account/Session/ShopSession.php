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

use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Hook\ActionShopAccessTokenRefreshAfter;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;

class ShopSession extends Session implements SessionInterface
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @var ShopIdentity
     */
    protected $shopIdentity;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param OAuth2Service $oAuth2Service
     * @param LinkShop $linkShop
     * @param CommandBus $commandBus
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        OAuth2Service $oAuth2Service,
        ShopIdentity $shopIdentity,
        CommandBus $commandBus
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->oAuth2Service = $oAuth2Service;
        $this->shopIdentity = $shopIdentity;
        $this->commandBus = $commandBus;
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

            $this->setToken(
                $accessToken->access_token,
                $accessToken->refresh_token
            );

            $token = $this->getToken();

            \Hook::exec(ActionShopAccessTokenRefreshAfter::getName(), ['token' => $token]);

            return $token;
        } catch (OAuth2Exception $e) {
        } catch (\Throwable $e) {
            /* @phpstan-ignore-next-line */
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
     * @return AccessToken
     *
     * @throws OAuth2Exception
     */
    protected function getAccessToken($shopUid)
    {
        $audience = [
            'shop_' . $shopUid,
            //'https://accounts-api.distribution.prestashop.net/shops/' . $shopUid,
            //'another.audience'
        ];

        return $this->oAuth2Service->getAccessTokenByClientCredentials([], $audience);
    }

    /**
     * @return string
     */
    private function getShopUuid()
    {
        return $this->shopIdentity->getShopUuid();
    }
}
