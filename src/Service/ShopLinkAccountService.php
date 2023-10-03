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

namespace PrestaShop\Module\PsAccounts\Service;

use Module;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\DTO\Api\UpdateShopLinkAccountRequest;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use Ps_accounts;

class ShopLinkAccountService
{
    /**
     * @var RsaKeysProvider
     */
    private $rsaKeysProvider;

    /**
     * @var ShopTokenRepository
     */
    private $shopTokenRepository;

    /**
     * @var UserTokenRepository
     */
    private $userTokenRepository;

    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param RsaKeysProvider $rsaKeysProvider
     * @param ShopTokenRepository $shopTokenRepository
     * @param UserTokenRepository $userTokenRepository
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(
        RsaKeysProvider $rsaKeysProvider,
        ShopTokenRepository $shopTokenRepository,
        UserTokenRepository $userTokenRepository,
        Oauth2Client $oauth2Client,
        ConfigurationRepository $configurationRepository
    ) {
        $this->rsaKeysProvider = $rsaKeysProvider;
        $this->shopTokenRepository = $shopTokenRepository;
        $this->userTokenRepository = $userTokenRepository;
        $this->oauth2Client = $oauth2Client;
        $this->configuration = $configurationRepository;
    }

    /**
     * @return AccountsClient
     *
     * @throws \Exception
     */
    public function getAccountsClient()
    {
        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        return $module->getService(AccountsClient::class);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function unlinkShop()
    {
        return $this->getAccountsClient()
            ->deleteUserShop($this->configuration->getShopId());
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     *
     * @throws \Exception
     */
    public function resetLinkAccount()
    {
        $this->rsaKeysProvider->cleanupKeys();
        $this->shopTokenRepository->cleanupCredentials();
        $this->userTokenRepository->cleanupCredentials();
        $this->configuration->updateEmployeeId('');
        $this->configuration->updateLoginEnabled(false);
        $this->oauth2Client->delete();
        try {
            $this->rsaKeysProvider->generateKeys();
        } catch (\Exception $e) {
        }
        $this->configuration->updateShopUnlinkedAuto(false);
    }

    /**
     * @param UpdateShopLinkAccountRequest $payload
     * @param bool $verifyTokens
     *
     * @return void
     *
     * @throws RefreshTokenException
     */
    public function updateLinkAccount(UpdateShopLinkAccountRequest $payload, $verifyTokens = false)
    {
        if ($verifyTokens) {
            $payload->shop_token = $this->shopTokenRepository->verifyToken($payload->shop_token, $payload->shop_refresh_token);
            $payload->user_token = $this->userTokenRepository->verifyToken($payload->user_token, $payload->user_refresh_token);
        }

        $this->shopTokenRepository->updateCredentials($payload->shop_token, $payload->shop_refresh_token);
        $this->userTokenRepository->updateCredentials($payload->user_token, $payload->user_refresh_token);
        $this->configuration->updateEmployeeId($payload->employee_id);
        $this->configuration->updateLoginEnabled(true);
        $this->configuration->updateShopUnlinkedAuto(false);
    }

    /**
     * @return void
     *
     * @throws SshKeysNotFoundException
     */
    public function prepareLinkAccount()
    {
        $this->rsaKeysProvider->generateKeys();
    }

    /**
     * @return bool
     */
    public function isAccountLinked()
    {
        return $this->shopTokenRepository->getOrRefreshToken()
            && $this->userTokenRepository->getOrRefreshToken();
    }

    /**
     * @return bool
     */
    public function isAccountLinkedV4()
    {
        return $this->shopTokenRepository->getOrRefreshToken()
            && !$this->userTokenRepository->getOrRefreshToken()
            && $this->userTokenRepository->getTokenEmail();
    }

    /**
     * @param string $hmac
     * @param string $uid
     * @param string $path
     *
     * @return void
     *
     * @throws HmacException
     */
    public function writeHmac($hmac, $uid, $path)
    {
        if (!is_dir($path)) {
            mkdir($path);
        }

        if (!is_writable($path)) {
            throw new HmacException('Directory isn\'t writable');
        }

        file_put_contents($path . $uid . '.txt', $hmac);
    }
}
