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

use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\TokenClientInterface;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use Ps_accounts;

abstract class Session implements SessionInterface
{
    const MAX_REFRESH_TOKEN_ATTEMPTS = 3;

    /**
     * @var TokenClientInterface
     */
    protected $apiClient;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var AnalyticsService
     */
    protected $analyticsService;

    /**
     * @var array
     */
    private $refreshTokenErrors;

    /**
     * @param TokenClientInterface $apiClient
     * @param ConfigurationRepository $configurationRepository
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        TokenClientInterface $apiClient,
        ConfigurationRepository $configurationRepository,
        AnalyticsService $analyticsService
    ) {
        $this->apiClient = $apiClient;
        $this->configurationRepository = $configurationRepository;
        $this->analyticsService = $analyticsService;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return Token
     *
     * @throws \Exception
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        $token = $this->getToken();
        $refreshToken = $token->getRefreshToken();

        if (!is_string($refreshToken) || '' === $refreshToken) {
            return $this->getToken();
        }

        if ($this->getRefreshTokenErrors($refreshToken)) {
            return $this->getToken();
        }

        if (true === $forceRefresh || $token->isExpired()) {
            try {
                $token = $this->refreshToken($refreshToken);
                $this->setToken((string) $token->getJwt(), $token->getRefreshToken());
            } catch (RefreshTokenException $e) {
                $this->setRefreshTokenErrors($refreshToken);
                Logger::getInstance()->debug($e);
            }
        }

        return $token;
    }

    /**
     * @param string $refreshToken
     *
     * @return Token idToken
     *
     * @throws RefreshTokenException
     * @throws \Exception
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getApiClient()->refreshToken($refreshToken);

        if ($response && true === $response['status']) {
            $this->onRefreshTokenSuccess();

            return $this->getTokenFromRefreshResponse($response);
        }

        if ($response['httpCode'] >= 400 && $response['httpCode'] < 500) {
            $this->onRefreshTokenFailure($response);
        }

        $errorMsg = isset($response['body']['message']) ?
            $response['body']['message'] :
            '';

        throw new RefreshTokenException(
            'Unable to refresh ' . static::getSessionName() . ' token : ' .
            $response['httpCode'] . ' ' . print_r($errorMsg, true)
        );
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function verifyToken($token)
    {
        $response = $this->getApiClient()->verifyToken($token);

        return $response && true === $response['status'];
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isEmailVerified()
    {
        $jwt = $this->getToken()->getJwt();

        // FIXME : just query sso api and don't refresh token everytime
        if (!$jwt instanceof NullToken &&
            !$jwt->claims()->get('email_verified')
        ) {
            try {
                $jwt = $this->getOrRefreshToken(true)->getJwt();
            } catch (RefreshTokenException $e) {
            }
        }

        return (bool) $jwt->claims()->get('email_verified');
    }

    /**
     * @param array $response
     *
     * @return Token
     */
    abstract protected function getTokenFromRefreshResponse(array $response);

    /**
     * @param array $response
     *
     * @return void
     *
     * @throws \PrestaShopException
     */
    protected function onRefreshTokenFailure(array $response)
    {
        $attempt = $this->configurationRepository->getRefreshTokenFailure(static::getSessionName());

        if ($attempt >= (static::MAX_REFRESH_TOKEN_ATTEMPTS - 1)) {
            $this->onMaxRefreshTokenAttempts($response);
            $this->configurationRepository->updateRefreshTokenFailure(static::getSessionName(), 0);

            return;
        }

        $this->configurationRepository->updateRefreshTokenFailure(
            static::getSessionName(),
            ++$attempt
        );
    }

    /**
     * @return void
     */
    protected function onRefreshTokenSuccess()
    {
        $this->configurationRepository->updateRefreshTokenFailure(static::getSessionName(), 0);
    }

    /**
     * @param array $response
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    protected function onMaxRefreshTokenAttempts(array $response)
    {
        /** @var Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $module->getService(ShopLinkAccountService::class);

        /** @var ShopProvider $shopProvider */
        $shopProvider = $module->getService(ShopProvider::class);

        $shop = $shopProvider->formatShopData((array) \Shop::getShop(
            $this->configurationRepository->getShopId()),
            'ps_accounts',
            false
        );

        $association->resetLinkAccount();
        $this->configurationRepository->updateShopUnlinkedAuto(true);

        $this->analyticsService->trackMaxRefreshTokenAttempts(
            $shop->user->uuid,
            $shop->user->email,
            $shop->uuid,
            $shop->frontUrl,
            $shop->url,
            static::getSessionName() . ' token',
            $response['httpCode']
        );
    }

    /**
     * @return TokenClientInterface
     */
    protected function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * @param string $refreshToken
     *
     * @return bool
     */
    protected function getRefreshTokenErrors($refreshToken)
    {
        return isset($this->refreshTokenErrors[$refreshToken]) && $this->refreshTokenErrors[$refreshToken];
    }

    /**
     * @param string $refreshToken
     *
     * @return void
     */
    protected function setRefreshTokenErrors($refreshToken)
    {
        $this->refreshTokenErrors[$refreshToken] = true;
    }
}
