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
 * Class PsAccountsService
 *
 * @package PrestaShop\Module\PsAccounts\Service
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
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var ShopTokenService
     */
    private $shopTokenService;

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
        \Ps_accounts $module,
        ConfigurationRepository $configuration
    ) {
        $config = $this->resolveConfig($config);
        $this->accountsUiUrl = $config['accounts_ui_url'];
        $this->ssoAccountUrl = $config['sso_account_url'];

        $this->configuration = $configuration;
        $this->module = $module;

        $this->link = $this->module->getService('ps_accounts.link');
    }

//    /**
//     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
//     *
//     * @param string $serviceName
//     *
//     * @return mixed
//     */
//    public function get($serviceName)
//    {
//        if (null === $this->container) {
//            $this->container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
//        }
//
//        return $this->container->get($serviceName);
//    }

    /**
     * @return string
     */
    public function getSuperAdminEmail()
    {
        return (new \Employee(1))->email;
    }

    /**
     * @return string | false
     */
    public function getShopUuidV4()
    {
        return $this->configuration->getShopUuid();
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
     * @return string | null
     */
    public function getRefreshToken()
    {
        return $this->shopTokenService->getRefreshToken();
    }

    /**
     * @return string
     */
    public function getSsoAccountUrl()
    {
        $url = $this->ssoAccountUrl;
        $langIsoCode = $this->module->getContext()->language->iso_code;

        return $url . '?lang=' . substr($langIsoCode, 0, 2);
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
}
