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

namespace PrestaShop\Module\PsAccounts\Account\Session\Firebase;

use Lcobucci\JWT\Parser;
use PrestaShop\Module\PsAccounts\Account\Session\Session;
use PrestaShop\Module\PsAccounts\Account\Session\SessionInterface;
use PrestaShop\Module\PsAccounts\Account\Token\Token;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ShopSession extends Session implements SessionInterface
{
    /**
     * @var AccountsClient
     */
    protected $apiClient;

    /**
     * @var \PrestaShop\Module\PsAccounts\Account\Session\ShopSession
     */
    protected $shopSession;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param AccountsClient $apiClient
     * @param \PrestaShop\Module\PsAccounts\Account\Session\ShopSession $shopSession
     */
    public function __construct(
        ConfigurationRepository $configurationRepository,
        AccountsClient $apiClient,
       \PrestaShop\Module\PsAccounts\Account\Session\ShopSession $shopSession
    ) {
        $this->configurationRepository = $configurationRepository;
        $this->apiClient = $apiClient;
        $this->shopSession = $shopSession;
    }

    /**
     * @param string $refreshToken
     *
     * @return Token
     *
     * @throws RefreshTokenException
     * @throws \Exception
     */
    public function refreshToken($refreshToken)
    {
        $this->shopSession->getOrRefreshToken();

        return $this->getToken();
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return new Token(
            $this->configurationRepository->getFirebaseIdToken()
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
    public function setToken($token, $refreshToken = null)
    {
        $parsed = (new Parser())->parse($token);

        $this->configurationRepository->updateShopUuid($parsed->claims()->get(Token::ID_OWNER_CLAIM));
        $this->configurationRepository->updateFirebaseIdAndRefreshTokens($token, $refreshToken);
    }
}
