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
use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Configuration\Configurable;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;
use PrestaShop\Module\PsAccounts\Exception\RsaSignedDataNotFoundException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Ps_accounts;

class ShopLinkAccountService implements Configurable
{
    /**
     * @var ShopKeysService
     */
    private $shopKeysService;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopTokenService
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
     * @param ShopProvider $shopProvider
     * @param ShopKeysService $shopKeysService
     * @param ShopTokenService $shopTokenService
     * @param ConfigurationRepository $configuration
     * @param Link $link
     *
     * @throws OptionResolutionException
     */
    public function __construct(
        array $config,
        ShopProvider $shopProvider,
        ShopKeysService $shopKeysService,
        ShopTokenService $shopTokenService,
        ConfigurationRepository $configuration,
        Link $link
    ) {
        $this->accountsUiUrl = $this->resolveConfig($config)['accounts_ui_url'];
        $this->shopProvider = $shopProvider;
        $this->shopKeysService = $shopKeysService;
        $this->shopTokenService = $shopTokenService;
        $this->configuration = $configuration;
        $this->link = $link;
    }

    /**
     * @return ServicesAccountsClient
     */
    public function getServicesAccountsClient()
    {
        /** @var Ps_accounts $module */
        $module = Module::getInstanceByName('ps_accounts');

        return $module->getService(ServicesAccountsClient::class);
    }

    /**
     * @param array $bodyHttp
     * @param string $trigger
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function updateShopUrl($bodyHttp, $trigger)
    {
        if (array_key_exists('shop_id', $bodyHttp)) {
            // id for multishop
            $this->configuration->setShopId($bodyHttp['shop_id']);
        }

        $sslEnabled = $this->shopProvider->getShopContext()->sslEnabled();
        $protocol = $this->shopProvider->getShopContext()->getProtocol();
        $domain = $sslEnabled ? $bodyHttp['domain_ssl'] : $bodyHttp['domain'];

        $uuid = $this->configuration->getShopUuid();

        $response = false;
        $boUrl = $this->replaceScheme(
            $this->link->getAdminLink('AdminModules', true),
            $protocol . '://' . $domain
        );

        if ($uuid && strlen($uuid) > 0) {
            $response = $this->getServicesAccountsClient()->updateShopUrl(
                $uuid,
                [
                    'protocol' => $protocol,
                    'domain' => $domain,
                    'boUrl' => $boUrl,
                    'trigger' => $trigger,
                ]
            );
        }

        return $response;
    }

    /**
     * @param string $psxName
     *
     * @return string
     *
     * @throws \PrestaShopException
     */
    public function getLinkAccountUrl($psxName)
    {
        $callback = $this->replaceScheme(
            $this->link->getAdminLink('AdminModules', true) . '&configure=' . $psxName
        );

        $protocol = $this->shopProvider->getShopContext()->getProtocol();
        $currentShop = $this->shopProvider->getCurrentShop($psxName);
        $domainName = $this->shopProvider->getShopContext()->sslEnabled() ? $currentShop['domainSsl'] : $currentShop['domain'];

        $queryParams = [
            'bo' => $callback,
            'pubKey' => $this->shopKeysService->getPublicKey(),
            'next' => $this->replaceScheme(
                $this->link->getAdminLink('AdminConfigureHmacPsAccounts')
            ),
            'name' => $currentShop['name'],
            'lang' => $this->shopProvider->getShopContext()->getContext()->language->iso_code,
        ];

        $queryParamsArray = [];
        foreach ($queryParams as $key => $value) {
            $queryParamsArray[] = $key . '=' . urlencode($value);
        }
        $strQueryParams = implode('&', $queryParamsArray);

        return $this->accountsUiUrl . '/shop/account/link/' . $protocol . '/' . $domainName
            . '/' . $protocol . '/' . $domainName . '/' . $psxName . '?' . $strQueryParams;
    }

    /**
     * @param array $queryParams
     * @param string $rootDir
     *
     * @return string
     *
     * @throws HmacException
     * @throws QueryParamsException
     * @throws RsaSignedDataNotFoundException
     */
    public function getVerifyAccountUrl(array $queryParams, $rootDir)
    {
        foreach (
            [
                'hmac' => '/[a-zA-Z0-9]{8,64}/',
                'uid' => '/[a-zA-Z0-9]{8,64}/',
                'slug' => '/[-_a-zA-Z0-9]{8,255}/',
            ] as $key => $value
        ) {
            if (!array_key_exists($key, $queryParams)) {
                throw new QueryParamsException('Missing query params');
            }

            if (!preg_match($value, $queryParams[$key])) {
                throw new QueryParamsException('Invalid query params');
            }
        }

        $this->writeHmac($queryParams['hmac'], $queryParams['uid'], $rootDir . '/upload/');

        if (empty($this->shopKeysService->getSignature())) {
            throw new RsaSignedDataNotFoundException('RSA signature not found');
        }

        $url = $this->accountsUiUrl;

        if ('/' === substr($url, -1)) {
            $url = substr($url, 0, -1);
        }

        return $url . '/shop/account/verify/' . $queryParams['uid']
            . '?shopKey=' . urlencode($this->shopKeysService->getSignature());
    }

    /**
     * @return array
     *
     * @throws SshKeysNotFoundException
     */
    public function unlinkShop()
    {
        $response = $this->getServicesAccountsClient()->deleteShop((string) $this->configuration->getShopUuid());

        // Réponse: 200: Shop supprimé avec payload contenant un message de confirmation
        // Réponse: 404: La shop n'existe pas (not found)
        // Réponse: 401: L'utilisateur n'est pas autorisé à supprimer cette shop

        if ($response['status'] && 200 === $response['httpCode']
            || 404 === $response['httpCode']) {
            $this->resetOnboardingData();

            // FIXME regenerate rsa keys
            $this->shopKeysService->regenerateKeys();
        }

        return $response;
    }

    /**
     * Empty onboarding configuration values
     *
     * @return void
     */
    public function resetOnboardingData()
    {
        $this->configuration->updateAccountsRsaPrivateKey('');
        $this->configuration->updateAccountsRsaPublicKey('');
        $this->configuration->updateAccountsRsaSignData('');

        $this->configuration->updateFirebaseIdAndRefreshTokens('', '');
        $this->configuration->updateFirebaseEmail('');
        $this->configuration->updateFirebaseEmailIsVerified(false);

        $this->configuration->updateShopUuid('');
    }

    /**
     * @param string $psxName
     *
     * @return void
     *
     * @throws SshKeysNotFoundException
     * @throws \PrestaShopException
     */
    public function manageOnboarding($psxName)
    {
        $this->shopKeysService->generateKeys();

        $this->updateOnboardingData($psxName);
    }

    /**
     * Only callable during onboarding
     *
     * Prepare onboarding data
     *
     * @param string $psxName
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function updateOnboardingData($psxName)
    {
        $email = \Tools::getValue('email');
        $emailVerified = \Tools::getValue('emailVerified');
        $customToken = \Tools::getValue('adminToken');

        if (is_string($customToken)) {
            if (false === $this->shopKeysService->hasKeys()) {
                throw new \Exception('SSH keys were not found');
            }

            if (!$this->shopTokenService->exchangeCustomTokenForIdAndRefreshToken($customToken)) {
                throw new \Exception('Unable to get Firebase token');
            }

            if (!empty($email)) {
                $this->configuration->updateFirebaseEmail($email);

                if (!empty($emailVerified)) {
                    $this->configuration->updateFirebaseEmailIsVerified('true' === $emailVerified);
                }

                // FIXME : quick and dirty fix
                \Tools::redirectAdmin(
                    $this->link->getAdminLink('AdminModules', true, [], [
                        'configure' => $psxName,
                    ])
                );
            }
        }
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

    /**
     * @param string $hmac
     * @param string $uid
     * @param string $path
     *
     * @return void
     *
     * @throws HmacException
     */
    private function writeHmac($hmac, $uid, $path)
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
     * @param string $url
     * @param string $replacement
     *
     * @return string
     */
    private function replaceScheme($url, $replacement = '')
    {
        return preg_replace('/^https?:\/\/[^\/]+/', $replacement, $url);
    }
}
