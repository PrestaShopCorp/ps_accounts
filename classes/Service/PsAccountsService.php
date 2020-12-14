<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use Module;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Api\Client\ServicesAccountsClient;
use PrestaShop\Module\PsAccounts\Configuration\ConfigOptionsResolver;
use PrestaShop\Module\PsAccounts\Configuration\Configurable;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;
use PrestaShop\Module\PsAccounts\Exception\PsAccountsRsaSignDataEmptyException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tools;

/**
 * Construct the psaccounts service.
 */
class PsAccountsService implements Configurable
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Link
     */
    protected $link;

    /**
     * @var string
     */
    protected $accountsUiUrl;

    /**
     * @var string
     */
    protected $ssoAccountUrl;

    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var string | null
     */
    private $psxName = null;

    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var ShopProvider
     */
    private $shopProvider;

    /**
     * @var ShopTokenService
     */
    private $shopTokenService;

    /**
     * @var ShopKeysService
     */
    private $shopKeysService;

    /**
     * PsAccountsService constructor.
     *
     * @param array $config
     * @param ConfigurationRepository $configuration
     * @param \Ps_accounts $module
     *
     * @throws \Exception
     */
    public function __construct(
        array $config,
        ConfigurationRepository $configuration,
        \Ps_accounts $module
    ) {
        $config = $this->resolveConfig($config);
        $this->accountsUiUrl = $config['accounts_ui_url'];
        $this->ssoAccountUrl = $config['sso_account_url'];

        $this->configuration = $configuration;
        $this->module = $module;

        $this->link = $this->module->getService('ps_accounts.link');
    }

    /**
     * @param string $psxName
     *
     * @return void
     */
    public function setPsxName($psxName)
    {
        $this->psxName = $psxName;
    }

    /**
     * @return string | null
     */
    public function getPsxName()
    {
        return $this->psxName;
    }

    /**
     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function get($serviceName)
    {
        if (null === $this->container) {
            $this->container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        }

        return $this->container->get($serviceName);
    }

    /**
     * @return string | null
     */
    public function getFirebaseRefreshToken()
    {
        return $this->configuration->getFirebaseRefreshToken() ?: null;
    }

    /**
     * @return string
     */
    public function getSuperAdminEmail()
    {
        return (new \Employee(1))->email;
    }

    /**
     * @return string | null
     */
    public function getEmail()
    {
        return $this->configuration->getFirebaseEmail() ?: null;
    }

    /**
     * @return bool
     */
    public function isEmailValidated()
    {
        return $this->configuration->firebaseEmailIsVerified();
    }

    /**
     * @return string | false
     */
    public function getShopUuidV4()
    {
        return $this->configuration->getShopUuid();
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

        $uuid = $this->getShopUuidV4();

        $response = false;
        $boUrl = $this->replaceScheme(
            $this->link->getAdminLink('AdminModules', true),
            $protocol . '://' . $domain
        );

        if ($uuid && strlen($uuid) > 0) {

            /** @var ServicesAccountsClient $servicesAccountsClient */
            $servicesAccountsClient = $this->module->getService(ServicesAccountsClient::class);

            $response = $servicesAccountsClient->updateShopUrl(
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
     * Get the user firebase token.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getOrRefreshToken()
    {
        return $this->shopTokenService->getOrRefreshToken();
    }

    /**
     * @return string
     */
    public function getManageAccountLink()
    {
        $url = $this->ssoAccountUrl;
        $langIsoCode = $this->module->getContext()->language->iso_code;

        return $url . '?lang=' . substr($langIsoCode, 0, 2);
    }

    /**
     * @return string
     */
    public function getSsoAccountUrl()
    {
        return $this->ssoAccountUrl;
    }

    /**
     * Generate ajax admin link with token
     * available via PsAccountsPresenter into page dom,
     * ex :
     * let url = window.contextPsAccounts.adminAjaxLink + '&action=unlinkShop'
     *
     * @return string
     *
     * @throws \PrestaShopException
     */
    public function getAdminAjaxUrl()
    {
//        Tools::getAdminTokenLite('AdminAjaxPsAccounts'));
        return $this->link->getAdminLink('AdminAjaxPsAccounts', true, [], ['ajax' => 1]);
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
            'sso_account_url',
        ]))->resolve($config, $defaults);
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
