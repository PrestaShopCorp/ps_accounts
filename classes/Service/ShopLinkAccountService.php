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
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Configuration\Configurable;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;
use PrestaShop\Module\PsAccounts\Exception\RsaSignedDataNotFoundException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use Ps_accounts;

class ShopLinkAccountService implements Configurable
{
    /**
     * @var RsaKeysProvider
     */
    private $rsaKeysProvider;

    /**
     * @var ShopTokenRepository
     */
    private $shopTokenService;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var string
     */
    private $accountsUiUrl;

    /**
     * ShopLinkAccountService constructor.
     *
     * @param array $config
     * @param RsaKeysProvider $rsaKeysProvider
     * @param ShopTokenRepository $shopTokenRepository
     * @param ConfigurationRepository $configuration
     * @param Link $link
     *
     * @throws OptionResolutionException
     */
    public function __construct(
        array $config,
        RsaKeysProvider $rsaKeysProvider,
        ShopTokenRepository $shopTokenRepository,
        ConfigurationRepository $configuration,
        Link $link
    ) {
        $this->accountsUiUrl = $this->resolveConfig($config)['accounts_ui_url'];
        $this->rsaKeysProvider = $rsaKeysProvider;
        $this->shopTokenService = $shopTokenRepository;
        $this->configuration = $configuration;
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
            (string) $this->configuration->getUserFirebaseUuid(),
            (string) $this->configuration->getShopUuid()
        );

        // Réponse: 200: Shop supprimé avec payload contenant un message de confirmation
        // Réponse: 404: La shop n'existe pas (not found)
        // Réponse: 401: L'utilisateur n'est pas autorisé à supprimer cette shop

        if ($response['status'] && 200 === $response['httpCode']
            || 404 === $response['httpCode']) {
            $this->resetLinkAccount();
        }

        return $response;
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     */
    public function resetLinkAccount()
    {
        // FIXME : employee_id, user_tokens ...

        $this->configuration->updateAccountsRsaPrivateKey('');
        $this->configuration->updateAccountsRsaPublicKey('');
        $this->configuration->updateAccountsRsaSignData('');

        $this->configuration->updateFirebaseIdAndRefreshTokens('', '');
        $this->configuration->updateFirebaseEmail('');
        $this->configuration->updateFirebaseEmailIsVerified(false);

        $this->configuration->updateShopUuid('');
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
        return $this->shopTokenService->getToken()
            && $this->configuration->getFirebaseEmail();
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

    /**
     * @param array $config
     * @param array $defaults
     *
     * @return array|mixed
     *
     * @throws OptionResolutionException
     */
    public function resolveConfig(array $config, array $defaults = [])
    {
        return (new ConfigOptionsResolver([
            'accounts_ui_url',
        ]))->resolve($config, $defaults);
    }
}
