<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Entity;

use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\SessionInterface;
use PrestaShop\Module\PsAccounts\Domain\Shop\Contract\TokenClientInterface;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Logger\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Ps_accounts;

abstract class AbstractSession implements SessionInterface
{
    protected const MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE = 3;

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
     * @return Token
     *
     * @throws \Exception
     */
    public function getOrRefreshToken(bool $forceRefresh = false): Token
    {
        $token = $this->getToken();

        if (true === $forceRefresh || $token->isExpired()) {
            $refreshToken = $token->getRefreshToken();
            if (!empty($refreshToken)) {
                try {
                    $token = $this->refreshToken($refreshToken);
                    $this->setToken((string) $token->getJwt(), $token->getRefreshToken());
                } catch (RefreshTokenException $e) {
                    Logger::getInstance()->debug($e);
                }
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
    public function refreshToken(string $refreshToken): Token
    {
        $response = $this->getApiClient()->refreshToken($refreshToken);

        if ($response && true === $response['status']) {
            $this->onRefreshTokenSuccess();

            return $this->getTokenFromRefreshResponse($response);
        }

        if ($response['httpCode'] >= 400 && $response['httpCode'] < 500) {
            $this->onRefreshTokenFailure();
        }

        throw new RefreshTokenException('Unable to refresh ' . static::getSessionName() . ' token : ' . $response['httpCode'] . ' ' . print_r($response['body']['message'] ?? '', true));
    }

    public function verifyToken(string $token): bool
    {
        $response = $this->getApiClient()->verifyToken($token);

        return $response && true === $response['status'];
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isEmailVerified(): bool
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

    abstract protected function getTokenFromRefreshResponse(array $response): Token;

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function onRefreshTokenFailure(): void
    {
        $attempt = $this->configurationRepository->getRefreshTokenFailure(static::getSessionName());

        if ($attempt >= (static::MAX_TRIES_BEFORE_CLEAN_CREDENTIALS_ON_REFRESH_TOKEN_FAILURE - 1)) {
            $this->onMaxRefreshTokenAttempts();
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
    protected function onRefreshTokenSuccess(): void
    {
        $this->configurationRepository->updateRefreshTokenFailure(static::getSessionName(), 0);
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
