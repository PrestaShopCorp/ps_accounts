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

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;

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
     * @var ShopTokenRepository
     */
    private $shopTokenRepository;

    /**
     * @var UserTokenRepository
     */
    private $userTokenRepository;

    /**
     * PsAccountsService constructor.
     *
     * @param \Ps_accounts $module
     * @param ShopTokenRepository $shopTokenRepository
     * @param UserTokenRepository $userTokenRepository
     * @param Link $link
     */
    public function __construct(
        \Ps_accounts $module,
        ShopTokenRepository $shopTokenRepository,
        UserTokenRepository $userTokenRepository,
        Link $link
    ) {
        $this->module = $module;
        $this->shopTokenRepository = $shopTokenRepository;
        $this->userTokenRepository = $userTokenRepository;
        $this->link = $link;
    }

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
        return $this->shopTokenRepository->getTokenUuid();
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
        return $this->shopTokenRepository->getOrRefreshToken();
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->shopTokenRepository->getRefreshToken();
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->shopTokenRepository->getToken();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isEmailValidated()
    {
        return $this->userTokenRepository->getTokenEmailVerified();
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->userTokenRepository->getTokenEmail();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isAccountLinked()
    {
        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        return $shopLinkAccountService->isAccountLinked();
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
