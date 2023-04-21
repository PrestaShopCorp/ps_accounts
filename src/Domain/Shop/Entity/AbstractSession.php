<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\SessionInterface;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\TokenClientInterface;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Logger\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Ps_accounts;

abstract class AbstractSession implements SessionInterface
{
    public const MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE = 3;

    // TODO: extract a ResponseInterface from those 3 consts
    protected const TOKEN_TYPE = '';
    protected const RESPONSE_TOKEN_KEY = '';
    protected const RESPONSE_REFRESH_TOKEN_KEY = '';

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var TokenClientInterface
     */
    protected $apiClient;

    public function __construct(
        TokenClientInterface $apiClient,
        ConfigurationRepository $configurationRepository
    ) {
        $this->apiClient = $apiClient;
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * @param bool $forceRefresh
     *
     * @return Token|null
     *
     * @throws \Throwable
     */
    public function getOrRefreshToken(bool $forceRefresh = false): Token
    {
        $token = $this->getToken();

        if (true === $forceRefresh || !$token || $token->isExpired()) {
            $refreshToken = $token->getRefreshToken();
            if (is_string($refreshToken) && '' != $refreshToken) {
                try {
                    $token = $this->refreshToken($refreshToken);
                    $this->setToken((string) $token->getToken(), $token->getRefreshToken());
                } catch (RefreshTokenException $e) {
                    Logger::getInstance()->debug($e);
                }
            }
        }

        // return $this->getToken();
        return $token;
    }

    /**
     * @param string $refreshToken
     *
     * @return Token|null idToken
     *
     * @throws RefreshTokenException
     * @throws \Exception
     */
    public function refreshToken(string $refreshToken): Token
    {
        $response = $this->getApiClient()->refreshToken($refreshToken);

        if ($response && true === $response['status']) {
            $token = new Token(
                $response['body'][static::RESPONSE_TOKEN_KEY],
                $response['body'][static::RESPONSE_REFRESH_TOKEN_KEY]
            );

            $this->onRefreshTokenSuccess();

            return $token;
        }

        if ($response['httpCode'] >= 400 && $response['httpCode'] < 500) {
            $this->onRefreshTokenFailure();
        }

        throw new RefreshTokenException('Unable to refresh ' . static::TOKEN_TYPE . ' token : ' . $response['httpCode'] . ' ' . print_r($response['body']['message'] ?? '', true));
    }

    public function verifyToken(string $token): bool
    {
        $response = $this->getApiClient()->verifyToken($token);

        return $response && true === $response['status'];
    }

    /**
     * @return bool
     *
     * @throws \Throwable
     */
    public function isEmailVerified(): bool
    {
        $token = $this->getToken();

        // FIXME : just query sso api and don't refresh token everytime
        if (null !== $token && !$token->getToken()->claims()->get('email_verified')) {
            try {
                $token = $this->getOrRefreshToken(true);
            } catch (RefreshTokenException $e) {
            }
        }

        return null !== $token && (bool) $token->getToken()->claims()->get('email_verified');
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function onRefreshTokenFailure(): void
    {
        $attempt = $this->configurationRepository->getRefreshTokenFailure(static::TOKEN_TYPE);

        if ($attempt >= (static::MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE - 1)) {
            $this->onMaxRefreshTokenAttempts();
            $this->configurationRepository->updateRefreshTokenFailure(static::TOKEN_TYPE, 0);

            return;
        }

        $this->configurationRepository->updateRefreshTokenFailure(
            static::TOKEN_TYPE,
            ++$attempt
        );
    }

    /**
     * @return void
     */
    protected function onRefreshTokenSuccess(): void
    {
        $this->configurationRepository->updateRefreshTokenFailure(static::TOKEN_TYPE, 0);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function onMaxRefreshTokenAttempts(): void
    {
        /** @var Ps_accounts $module */
        $module = \Module::getInstanceByName('ps_accounts');

        /** @var Account $service */
        $service = $module->getService(Account::class);

        $service->resetLink();
    }

    protected function getApiClient(): TokenClientInterface
    {
        return $this->apiClient;
    }
}
