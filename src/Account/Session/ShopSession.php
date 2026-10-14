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

use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Hook\ActionShopAccessTokenRefreshAfter;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Exception;
use PrestaShop\Module\PsAccounts\Service\OAuth2\OAuth2Service;
use PrestaShop\Module\PsAccounts\Service\OAuth2\Resource\AccessToken;

class ShopSession extends Session implements SessionInterface
{
    /**
     * Clock offsets below this value (in seconds) are measurement noise: "iat" is
     * truncated to the second and the token reaches the shop after a network round trip.
     */
    const CLOCK_OFFSET_THRESHOLD = 5;

    /**
     * Clock offsets beyond this value (in seconds) are considered bogus and ignored.
     */
    const CLOCK_OFFSET_MAX = 86400;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var OAuth2Service
     */
    protected $oAuth2Service;

    /**
     * @var string
     */
    protected $tokenAudience;

    /**
     * @var int
     */
    protected $tokenExpirationLeeway;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param OAuth2Service $oAuth2Service
     * @param string $tokenAudience
     * @param int $tokenExpirationLeeway tolerance in seconds when checking
     *                                   the local access token expiration before deciding to refresh it
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        OAuth2Service $oAuth2Service,
        $tokenAudience,
        $tokenExpirationLeeway = 0
    ) {
        parent::__construct();

        $this->configurationRepository = $configurationRepository;
        $this->oAuth2Service = $oAuth2Service;
        $this->tokenAudience = $tokenAudience;
        $this->tokenExpirationLeeway = (int) $tokenExpirationLeeway;
    }

    /**
     * Resolves the shop store audience when $audience is omitted. When passed
     * explicitly — including an empty array — it is forwarded as-is, so a caller
     * can force an empty audience. $scope is always forwarded unchanged.
     *
     * Note: func_num_args() is used (rather than a null default) to tell an
     * omitted $audience apart from an explicit "[]" while keeping the array type
     * hint. This avoids both the PHP 8.4 implicitly-nullable deprecation (removed
     * in PHP 9.0) and the pre-7.2 "Declaration should be compatible" variance
     * warning against the parent/interface signature (array $audience = []).
     *
     * @param bool $forceRefresh
     * @param bool $throw
     * @param array $scope
     * @param array $audience omit to resolve the shop store audience; pass (even []) to force it
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function getValidToken($forceRefresh = false, $throw = true, array $scope = [], array $audience = [])
    {
        if (func_num_args() < 4) {
            $audience = [
                'store/' . $this->getStatusManager()->getCloudShopId(),
                $this->tokenAudience,
            ];
        }

        return parent::getValidToken($forceRefresh, $throw, $scope, $audience);
    }

    /**
     * @param string $refreshToken
     * @param array $scope
     * @param array $audience
     *
     * @return Token
     *
     * @throws RefreshTokenException
     */
    public function refreshToken($refreshToken = null, array $scope = [], array $audience = [])
    {
        try {
            $accessToken = $this->getAccessToken($scope, $audience);

            $this->updateClockOffset(new Token($accessToken->access_token));

            $this->setToken(
                $accessToken->access_token,
                $accessToken->refresh_token
            );

            $token = $this->getToken();

            \Hook::exec(ActionShopAccessTokenRefreshAfter::getName(), ['token' => $token]);

            return $token;
        } catch (OAuth2Exception $e) {
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
        throw new RefreshTokenException('Unable to refresh shop token : ' . $e->getMessage(), 0, $e);
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return new Token(
            $this->configurationRepository->getAccessToken(),
            null,
            $this->resolveTokenExpirationLeeway(),
            $this->configurationRepository->getClockOffset()
        );
    }

    /**
     * Measures the offset between the local clock and the authorization server clock on a
     * freshly issued token, so that expiration checks rely on the authorization server time.
     *
     * @param Token $freshToken
     *
     * @return void
     */
    protected function updateClockOffset(Token $freshToken)
    {
        $offset = $freshToken->measureClockOffset();
        if (null === $offset || abs($offset) > self::CLOCK_OFFSET_MAX) {
            return;
        }

        if (abs($offset) < self::CLOCK_OFFSET_THRESHOLD) {
            $offset = 0;
        }

        if (abs($offset - $this->configurationRepository->getClockOffset()) < self::CLOCK_OFFSET_THRESHOLD) {
            return;
        }

        $this->configurationRepository->updateClockOffset($offset);

        if (0 !== $offset) {
            Logger::getInstance()->warning(sprintf(
                'Server clock is %d seconds %s the authorization server: token expiration checks are corrected, please synchronize the server clock (NTP)',
                abs($offset),
                $offset > 0 ? 'behind' : 'ahead of'
            ));
        }
    }

    /**
     * @return int
     */
    protected function resolveTokenExpirationLeeway()
    {
        $leeway = $this->configurationRepository->getTokenExpirationLeeway();

        return is_int($leeway) ? $leeway : $this->tokenExpirationLeeway;
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
     * @param array $scope
     * @param array $audience
     *
     * @return AccessToken
     *
     * @throws OAuth2Exception
     */
    protected function getAccessToken(array $scope = [], array $audience = [])
    {
        return $this->oAuth2Service->getAccessTokenByClientCredentials($scope, $audience);
    }
}
