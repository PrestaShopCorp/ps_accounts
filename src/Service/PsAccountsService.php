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

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\EmployeeAccountRepository;

/**
 * Class PsAccountsService
 */
class PsAccountsService
{
    /**
     * @var Link
     */
    protected $link;

    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @param \Ps_accounts $module
     *
     * @throws \Exception
     */
    public function __construct(\Ps_accounts $module)
    {
        $this->module = $module;
        $this->shopSession = $this->module->getService(ShopSession::class);
        $this->ownerSession = $this->module->getService(OwnerSession::class);
        $this->link = $this->module->getService(Link::class);
        $this->linkShop = $module->getService(LinkShop::class);
    }

    /**
     * @return string
     */
    public function getSuperAdminEmail()
    {
        return (new \Employee(1))->email;
    }

    /**
     * @return string
     *
     * @deprecated deprecated since version 5.0
     */
    public function getShopUuidV4()
    {
        return $this->getShopUuid();
    }

    /**
     * @return string
     */
    public function getShopUuid()
    {
        return $this->linkShop->getShopUuid();
    }

    /**
     * @return string
     */
    public function getOrRefreshToken()
    {
        try {
            return (string) $this->shopSession->getValidToken()->getJwt();
        } catch (RefreshTokenException $e) {
            return '';
        }
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->shopSession->getToken()->getRefreshToken();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->getOrRefreshToken();
    }

    /**
     * @return string
     *
     * @deprecated
     */
    public function getUserToken()
    {
        try {
            return (string) $this->ownerSession->getValidToken()->getJwt();
        } catch (RefreshTokenException $e) {
            return '';
        }
    }

    /**
     * @return string
     *
     * @deprecated deprecated since version 5.1.1
     */
    public function getUserUuidV4()
    {
        return $this->getUserUuid();
    }

    /**
     * @return string
     */
    public function getUserUuid()
    {
        return (string) $this->linkShop->getOwnerUuid();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isEmailValidated()
    {
        return $this->ownerSession->isEmailVerified();
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->linkShop->getOwnerEmail();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isAccountLinked()
    {
        return $this->linkShop->exists();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isAccountLinkedV4()
    {
        return $this->linkShop->existsV4();
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
     * @return string
     *
     * @throws \Exception
     */
    public function getAccountsVueCdn()
    {
        return $this->module->getParameter('ps_accounts.accounts_vue_cdn_url');
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getAccountsCdn()
    {
        return $this->module->getParameter('ps_accounts.accounts_cdn_url');
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function getLoginActivated()
    {
        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        return $configuration->getLoginEnabled() &&
            $configuration->getOauth2ClientId() &&
            $configuration->getOauth2ClientSecret();
    }

    /**
     * @param bool $enabled
     *
     * @return void
     *
     * @throws \Exception
     */
    public function enableLogin($enabled = true)
    {
        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        $configuration->updateLoginEnabled($enabled);
    }

    /**
     * @return EmployeeAccount|null
     */
    public function getEmployeeAccount()
    {
        $repository = new EmployeeAccountRepository();
        try {
            return $repository->findByEmployeeId(
                $this->module->getContext()->employee->id
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
