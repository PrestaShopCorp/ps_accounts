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

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PsAccountsService
 *
 * @package PrestaShop\Module\PsAccounts\Service
 */
class PsAccountsService
{
//    /**
//     * @var ContainerInterface
//     */
//    protected $container;

    /**
     * @var Link
     */
    protected $link;

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
     * @param \Ps_accounts $module
     * @param ShopTokenService $shopTokenService
     * @param ConfigurationRepository $configuration
     * @param Link $link
     */
    public function __construct(
        \Ps_accounts $module,
        ShopTokenService $shopTokenService,
        ConfigurationRepository $configuration,
        Link $link
    ) {
        $this->configuration = $configuration;
        $this->shopTokenService = $shopTokenService;
        $this->module = $module;
        $this->link = $link;
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
     * @return bool
     */
    public function isEmailValidated()
    {
        return $this->configuration->firebaseEmailIsVerified();
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
}
