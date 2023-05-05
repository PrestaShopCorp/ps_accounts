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
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\MigrateAndLinkV4ShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Account;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

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

    public function __construct(
        \Ps_accounts $module,
        ShopSession $shopSession,
        OwnerSession $ownerSession,
        Link $link
    ) {
        $this->module = $module;
        $this->shopSession = $shopSession;
        $this->ownerSession = $ownerSession;
        $this->link = $link;
    }

    public function getSuperAdminEmail(): string
    {
        return (new \Employee(1))->email;
    }

    /**
     * @deprecated deprecated since version 5.0
     */
    public function getShopUuidV4(): string
    {
        return $this->getShopUuid();
    }

    public function getShopUuid(): string
    {
        return (string) $this->shopSession->getToken()->getUuid();
    }

    /**
     * @throws \Exception
     */
    public function getOrRefreshToken(): string
    {
        return (string) $this->shopSession->getOrRefreshToken()->getJwt();
    }

    public function getRefreshToken(): ?string
    {
        return $this->shopSession->getToken()->getRefreshToken();
    }

    /**
     * @throws \Exception
     */
    public function getToken(): ?string
    {
        return (string) $this->shopSession->getOrRefreshToken()->getJwt();
    }

    /**
     * @deprecated deprecated since version 5.1.1
     */
    public function getUserUuidV4(): string
    {
        return $this->getUserUuid();
    }

    public function getUserUuid(): string
    {
        return (string) $this->ownerSession->getToken()->getUuid();
    }

    /**
     * @throws \Exception
     */
    public function isEmailValidated(): bool
    {
        return $this->ownerSession->isEmailVerified();
    }

    public function getEmail(): ?string
    {
        return $this->ownerSession->getToken()->getEmail();
    }

    /**
     * @throws \Exception
     */
    public function isAccountLinked(): bool
    {
        /** @var Account $shopAccount */
        $shopAccount = $this->module->getService(Account::class);

        return $shopAccount->isLinked();
    }

    /**
     * @throws \Exception
     */
    public function isAccountLinkedV4(): bool
    {
        /** @var Account $shopAccount */
        $shopAccount = $this->module->getService(Account::class);

        return $shopAccount->isLinkedV4();
    }

    /**
     * Generate ajax admin link with token
     * available via PsAccountsPresenter into page dom,
     * ex :
     * let url = window.contextPsAccounts.adminAjaxLink + '&action=unlinkShop'

     *
     * @throws \PrestaShopException
     */
    public function getAdminAjaxUrl(): string
    {
//        Tools::getAdminTokenLite('AdminAjaxPsAccounts'));
        return $this->link->getAdminLink('AdminAjaxPsAccounts', true, [], ['ajax' => 1]);
    }

    /**
     * @throws \Exception
     */
    public function getAccountsVueCdn(): string
    {
        return $this->module->getParameter('ps_accounts.accounts_vue_cdn_url');
    }

    /**
     * @throws \Exception
     */
    public function getAccountsCdn(): string
    {
        return $this->module->getParameter('ps_accounts.accounts_cdn_url');
    }

    /**
     * @return void
     *
     * @throws \PrestaShopException
     */
    public function autoReonboardOnV5()
    {
        /** @var CommandBus $commandBus */
        $commandBus = $this->module->getService(CommandBus::class);

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);

        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);

        /** @var Account $shopAccount */
        $shopAccount = $this->module->getService(Account::class);

        $allShops = $shopProvider->getShopsTree((string) $this->module->name);

        $flattenShops = [];

        foreach ($allShops as $shopGroup) {
            foreach ($shopGroup['shops'] as $shop) {
                $shop['multishop'] = (bool) $shopGroup['multishop'];
                $flattenShops[] = $shop;
            }
        }

        $isAlreadyReonboard = false;

        usort($flattenShops, function ($firstShop, $secondShop) {
            return (int) $firstShop['id'] - (int) $secondShop['id'];
        });
        foreach ($flattenShops as $shop) {
            if ($shop['isLinkedV4']) {
                if ($isAlreadyReonboard) {
                    $id = $conf->getShopId();
                    $conf->setShopId((int) $shop['id']);

                    $shopAccount->resetLink();

                    $conf->setShopId($id);
                } else {
                    $shop['employeeId'] = null;

                    $commandBus->handle(new MigrateAndLinkV4ShopCommand($shop['id'], $shop));
                    $isAlreadyReonboard = true;
                }
            }
        }
    }

    /**
     * @deprecated
     *
     * @throws \Exception
     */
    public function getLoginActivated(): bool
    {
        /** @var Login $login */
        $login = $this->module->getService(Login::class);

        return $login->isEnabled();
    }

    /**
     * @throws \Exception
     */
    public function getEmployeeAccount(): ?EmployeeAccount
    {
        /** @var Login $login */
        $login = $this->module->getService(Login::class);

        return $login->getLoggedInEmployeeAccount();
    }
}
