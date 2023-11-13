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

namespace PrestaShop\Module\PsAccounts\Repository;

use Exception;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Module;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use Ps_accounts;

/**
 * Class AbstractTokenRepository
 */
abstract class AbstractTokenRepository
{
    const MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE = 3;

    const TOKEN_TYPE = '';
    const TOKEN_KEY = '';
    const REFRESH_TOKEN_KEY = '';

    /**
     * @var ConfigurationRepository
     */
    protected $configuration;

    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * @var string
     */
    protected $tokenType;

    /**
     * @var array
     */
    protected $refreshTokenErrors = [];

    /**
     * AbstractTokenRepository constructor.
     *
     * @param ConfigurationRepository $configuration
     */
    public function __construct(
        ConfigurationRepository $configuration,
        AnalyticsService $analyticsService
    ) {
        $this->configuration = $configuration;
        $this->analyticsService = $analyticsService;
    }

    /**
     * @return TokenClientInterface
     *
     * @throws Exception
     */
    abstract protected function client();

    /**
     * @return Token|null
     */
    abstract public function getToken();

    /**
     * @return string
     */
    abstract public function getTokenUuid();

    /**
     * @return string
     */
    abstract public function getRefreshToken();

    /**
     * @return void
     */
    abstract public function cleanupCredentials();

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return void
     */
    abstract public function updateCredentials($idToken, $refreshToken);

    /**
     * @param bool $forceRefresh
     *
     * @return Token|null
     *
     * @throws Exception
     */
    public function getOrRefreshToken($forceRefresh = false)
    {
        $refreshToken = $this->getRefreshToken();

        if (!is_string($refreshToken) || '' === $refreshToken) {
            return $this->getToken();
        }

        if ($this->getRefreshTokenErrors($refreshToken)) {
            return $this->getToken();
        }

        if (true === $forceRefresh || $this->isTokenExpired()) {
            try {
                $token = $this->refreshToken($refreshToken, $newRefreshToken);
                $this->updateCredentials(
                    (string) $token,
                    $newRefreshToken
                );
            } catch (RefreshTokenException $e) {
                $this->setRefreshTokenErrors($refreshToken);
                Logger::getInstance()->debug($e);
            }
        }

        return $this->getToken();
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function isTokenExpired()
    {
        $token = $this->getToken();

        return $token ? $token->isExpired(new \DateTime()) : true;
    }

    /**
     * @param string $token
     *
     * @return Token|null
     */
    public function parseToken($token)
    {
        try {
            return (new Parser())->parse((string) $token);
        } catch (InvalidTokenStructure $e) {
            return null;
        }
    }

    /**
     * @param string $refreshToken
     * @param string $newRefreshToken
     *
     * @return Token|null
     *
     * @throws RefreshTokenException
     * @throws Exception
     */
    public function refreshToken($refreshToken, &$newRefreshToken = null)
    {
        $response = $this->client()->refreshToken($refreshToken);

        if ($response && true === $response['status']) {
            $token = $this->parseToken($response['body'][static::TOKEN_KEY]);
            $newRefreshToken = $response['body'][static::REFRESH_TOKEN_KEY];

            $this->onRefreshTokenSuccess();

            return $token;
        }

        if ($response['httpCode'] >= 400 && $response['httpCode'] < 500) {
            $this->onRefreshTokenFailure($response);
        }

        $errorMsg = isset($response['body']['message']) ? $response['body']['message'] : '';
        throw new RefreshTokenException('Unable to refresh ' . static::TOKEN_TYPE . ' token : ' . $response['httpCode'] . ' ' . print_r($errorMsg, true));
    }

    /**
     * @param string $idToken
     * @param string $refreshToken
     *
     * @return Token|null verified or refreshed token on success
     *
     * @throws RefreshTokenException
     * @throws Exception
     */
    public function verifyToken($idToken, $refreshToken)
    {
        $response = $this->client()->verifyToken($idToken);

        if ($response && true === $response['status']) {
            return $this->parseToken($idToken);
        }

        return $this->refreshToken($refreshToken);
    }

    /**
     * @param array $response
     *
     * @return void
     *
     * @throws Exception
     */
    protected function onRefreshTokenFailure($response)
    {
        $attempt = $this->configuration->getRefreshTokenFailure(static::TOKEN_TYPE);

        if ($attempt >= (static::MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE - 1)) {
            $this->onMaxRefreshTokenAttempts($response);
            $this->configuration->updateRefreshTokenFailure(static::TOKEN_TYPE, 0);

            return;
        }

        $this->configuration->updateRefreshTokenFailure(
            static::TOKEN_TYPE,
            ++$attempt
        );
    }

    /**
     * @return void
     */
    protected function onRefreshTokenSuccess()
    {
        $this->configuration->updateRefreshTokenFailure(static::TOKEN_TYPE, 0);
    }

    /**
     * @param array $response
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws Exception
     */
    protected function onMaxRefreshTokenAttempts($response)
    {
        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        /** @var ShopLinkAccountService $service */
        $service = $module->getService(ShopLinkAccountService::class);

        /** @var ShopProvider $shopProvider */
        $shopProvider = $module->getService(ShopProvider::class);

        $shopData = $shopProvider->formatShopData((array) \Shop::getShop(
            $this->configuration->getShopId()),
            'ps_accounts',
            false
        );

        $service->resetLinkAccount();
        $this->configuration->updateShopUnlinkedAuto(true);

        $module->getLogger()->debug('### TRACKED - A');

        $this->analyticsService->trackMaxRefreshTokenAttempts(
            (string) $shopData['user']['uuid'],
            (string) $shopData['user']['email'],
            (string) $shopData['uuid'],
            (string) $shopData['frontUrl'],
            (string) $shopData['url'],
            static::TOKEN_TYPE . ' token',
            $response['httpCode']
        );

        $module->getLogger()->debug('### TRACKED - B');
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
