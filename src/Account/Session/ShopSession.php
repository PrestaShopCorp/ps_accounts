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

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;

class ShopSession extends Session implements SessionInterface
{
    /**
     * @var AccountsClient
     */
    protected $apiClient;

    /**
     * @param AccountsClient $apiClient
     * @param ConfigurationRepository $configurationRepository
     * @param AnalyticsService $analyticsService
     */
    public function __construct(
        AccountsClient $apiClient,
        ConfigurationRepository $configurationRepository,
        AnalyticsService $analyticsService
    ) {
        parent::__construct($apiClient, $configurationRepository, $analyticsService);
    }

    /**
     * @return string
     */
    public static function getSessionName()
    {
        return 'shop';
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return new Token(
            $this->configurationRepository->getFirebaseIdToken(),
            $this->configurationRepository->getFirebaseRefreshToken()
        );
    }

    /**
     * @return void
     */
    public function cleanup()
    {
        $this->configurationRepository->updateShopUuid('');
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens('', '');
    }

    /**
     * @param string $token
     * @param string $refreshToken
     *
     * @return void
     */
    public function setToken($token, $refreshToken)
    {
        $parsed = (new Parser())->parse($token);

        $this->configurationRepository->updateShopUuid($parsed->claims()->get(Token::ID_OWNER_CLAIM));
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens($token, $refreshToken);
    }

    /**
     * @param array $response
     *
     * @return Token
     */
    protected function getTokenFromRefreshResponse(array $response)
    {
        return new Token(
            $response['body']['token'],
            $response['body']['refresh_token']
        );
    }
}
