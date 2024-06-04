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

<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Account\Command\MigrateAndLinkV4ShopCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\EmployeeAccountRepository;
=======
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Account\Entity\Login;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\MigrateAndLinkV4ShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Association;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Entity\EmployeeAccount;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

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
<<<<<<< HEAD

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
=======

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
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }

    public function getSuperAdminEmail(): string
    {
        return (new \Employee(1))->email;
    }

    /**
<<<<<<< HEAD
     * @return string
     *
=======
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @deprecated deprecated since version 5.0
     */
    public function getShopUuidV4(): string
    {
        return $this->getShopUuid();
    }

<<<<<<< HEAD
    /**
     * @return string
     */
    public function getShopUuid()
    {
        return $this->linkShop->getShopUuid();
    }

    /**
     * @return string
     *
=======
    public function getShopUuid(): string
    {
        return (string) $this->shopSession->getToken()->getUuid();
    }

    /**
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @throws \Exception
     */
    public function getOrRefreshToken(): string
    {
<<<<<<< HEAD
        return (string) $this->shopSession->getOrRefreshToken();
=======
        return (string) $this->shopSession->getOrRefreshToken()->getJwt();
    }

    public function getRefreshToken(): ?string
    {
        return $this->shopSession->getToken()->getRefreshToken();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }

    /**
     * @throws \Exception
     */
    public function getToken(): ?string
    {
<<<<<<< HEAD
        return $this->shopSession->getToken()->getRefreshToken();
    }

    /**
     * @return string|null
     *
     * @throws \Exception
     */
    public function getToken()
    {
        return (string) $this->shopSession->getOrRefreshToken();
    }

    /**
     * @return string|null
     *
     * @throws \Exception
     *
     * @deprecated
     */
    public function getUserToken()
    {
        return (string) $this->ownerSession->getOrRefreshToken();
    }

    /**
     * @return string
     *
=======
        return (string) $this->shopSession->getOrRefreshToken()->getJwt();
    }

    /**
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @deprecated deprecated since version 5.1.1
     */
    public function getUserUuidV4(): string
    {
        return $this->getUserUuid();
    }

<<<<<<< HEAD
    /**
     * @return string
     */
    public function getUserUuid()
    {
        return (string) $this->linkShop->getOwnerUuid();
=======
    public function getUserUuid(): string
    {
        return (string) $this->ownerSession->getToken()->getUuid();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }

    /**
     * @throws \Exception
     */
    public function isEmailValidated(): bool
    {
        return $this->ownerSession->isEmailVerified();
<<<<<<< HEAD
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
=======
    }

    public function getEmail(): ?string
    {
        return $this->ownerSession->getToken()->getEmail();
    }

    /**
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @throws \Exception
     */
    public function isAccountLinked(): bool
    {
<<<<<<< HEAD
        return $this->linkShop->exists();
=======
        /** @var Association $association */
        $association = $this->module->getService(Association::class);

        return $association->isLinked();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }

    /**
     * @throws \Exception
     */
    public function isAccountLinkedV4(): bool
    {
<<<<<<< HEAD
        return $this->linkShop->existsV4();
=======
        /** @var Association $association */
        $association = $this->module->getService(Association::class);

        return $association->isLinkedV4();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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
        return $this->link->getAdminLink('AdminAjaxPsAccounts', true, [], ['ajax' => 1]);
    }

    /**
<<<<<<< HEAD
     * @return string
=======
     * for compat only
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     *
     * @throws \Exception
     */
    public function getAccountsVueCdn(): string
    {
        return $this->module->getParameter('ps_accounts.accounts_vue_cdn_url');
    }

    /**
<<<<<<< HEAD
     * @return string
     *
=======
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @throws \Exception
     */
    public function getAccountsCdn(): string
    {
        return $this->module->getParameter('ps_accounts.accounts_cdn_url');
    }

    /**
     * @deprecated
     *
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function autoReonboardOnV5()
    {
        /** @var CommandBus $commandBus */
        $commandBus = $this->module->getService(CommandBus::class);

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);

        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);

<<<<<<< HEAD
        /** @var LinkShop $linkShop */
        $linkShop = $this->module->getService(LinkShop::class);

        /** @var CommandBus $commandBus */
        $commandBus = $this->module->getService(CommandBus::class);
=======
        /** @var Association $association */
        $association = $this->module->getService(Association::class);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

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
                $id = $conf->getShopId();
                if ($isAlreadyReonboard) {
                    $conf->setShopId((int) $shop['id']);

<<<<<<< HEAD
                    $commandBus->handle(new UnlinkShopCommand($shop['id']));
=======
                    $association->resetLink();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

                    $conf->setShopId($id);
                } else {
                    $shop['employeeId'] = null;

<<<<<<< HEAD
                    $commandBus->handle(new MigrateAndLinkV4ShopCommand($id, $shop));

=======
                    $commandBus->handle(new MigrateAndLinkV4ShopCommand($shop['id'], $shop));
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
                    $isAlreadyReonboard = true;
                }
            }
        }
    }

    /**
<<<<<<< HEAD
     * @return bool
     *
     * @throws \Exception
     */
    public function getLoginActivated()
=======
     * @throws \Exception
     */
    public function getLoginActivated(): bool
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    {
        /** @var Login $login */
        $login = $this->module->getService(Login::class);

        return $login->isEnabled();
    }

    /**
<<<<<<< HEAD
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
        if ($repository->isCompatPs16()) {
            return $repository->findByEmployeeId(
                $this->module->getContext()->employee->id
            );
        }

        return null;
=======
     * @throws \Exception
     */
    public function getEmployeeAccount(): ?EmployeeAccount
    {
        /** @var Login $login */
        $login = $this->module->getService(Login::class);

        return $login->getLoggedInEmployeeAccount();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }
}
