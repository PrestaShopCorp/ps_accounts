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
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
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
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Link
     */
    private $link;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param RsaKeysProvider $rsaKeysProvider
     * @param ShopTokenRepository $shopTokenRepository
     * @param UserTokenRepository $userTokenRepository
     * @param ConfigurationRepository $configurationRepository
     * @param Link $link
     */
    public function __construct(
        RsaKeysProvider $rsaKeysProvider,
        ShopTokenRepository $shopTokenRepository,
        UserTokenRepository $userTokenRepository,
        ConfigurationRepository $configurationRepository,
        Link $link
    ) {
        $this->rsaKeysProvider = $rsaKeysProvider;
        $this->shopTokenRepository = $shopTokenRepository;
        $this->userTokenRepository = $userTokenRepository;
        $this->configuration = $configurationRepository;
        $this->link = $link;
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
        $response = $this->getAccountsClient()->deleteUserShop(
            (string) $this->userTokenRepository->getTokenUuid(),
            (string) $this->shopTokenRepository->getTokenUuid()
        );

        return $response;
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     */
    public function resetLinkAccount()
    {
        $this->rsaKeysProvider->cleanupKeys();
        $this->shopTokenRepository->cleanupCredentials();
        $this->userTokenRepository->cleanupCredentials();
        $this->configuration->updateEmployeeId('');
        try {
            $this->rsaKeysProvider->generateKeys();
        } catch (\Exception $e) {
        }
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
        return $this->shopTokenRepository->getToken()
            && $this->userTokenRepository->getToken()
            && $this->configuration->getEmployeeId();
    }

    /**
     * @return bool
     */
    public function isAccountLinkedV4()
    {
        return $this->shopTokenRepository->getToken()
            && !$this->userTokenRepository->getToken()
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
