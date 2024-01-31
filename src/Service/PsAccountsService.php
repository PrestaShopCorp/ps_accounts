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

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\PsAccounts\Account\Command\MigrateAndLinkV4ShopCommand;
use PrestaShop\Module\PsAccounts\Account\Session\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
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

    /**
     * PsAccountsService constructor.
     *
     * @param \Ps_accounts $module
     * @param ShopSession $shopSession
     * @param OwnerSession $ownerSession
     * @param Link $link
     */
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

    /**
     * @return string
     */
    public function getSuperAdminEmail()
    {
        return (new \Employee(1))->email;
    }

    /**
     * @deprecated deprecated since version 5.0
     *
     * @return string|false
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
        return $this->shopSession->getToken()->getUuid();
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
        return (string) $this->shopSession->getOrRefreshToken();
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
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
        return $this->ownerSession->getToken()->getUuid();
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
        return $this->ownerSession->getToken()->getEmail();
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
     * @return bool
     *
     * @throws \Exception
     */
    public function isAccountLinkedV4()
    {
        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

        return $shopLinkAccountService->isAccountLinkedV4();
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
     * @return void
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function autoReonboardOnV5()
    {
        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);

        /** @var ConfigurationRepository $conf */
        $conf = $this->module->getService(ConfigurationRepository::class);

        /** @var ShopLinkAccountService $shopLinkAccountService */
        $shopLinkAccountService = $this->module->getService(ShopLinkAccountService::class);

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

                    $shopLinkAccountService->resetLinkAccount();

                    $conf->setShopId($id);
                } else {
                    $shop['employeeId'] = null;

                    /** @var CommandBus $commandBus */
                    $commandBus = $this->module->getService(CommandBus::class);
                    $commandBus->handle(new MigrateAndLinkV4ShopCommand($id, $shop));

                    $isAlreadyReonboard = true;
                }
            }
        }
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
     * @return EmployeeAccount|null
     */
    public function getEmployeeAccount()
    {
        $employeeId = $this->module->getContext()->employee->id;

        // FIXME: v1.6 compat
        if (!empty($employeeId) && method_exists($this->module, 'getContainer')) {
            /**
             * @phpstan-ignore-next-line
             *
             * @var EntityManagerInterface $entityManager
             */
            $entityManager = $this->module->getContainer()->get('doctrine.orm.entity_manager');

            /* @phpstan-ignore-next-line */
            $employeeAccountRepository = $entityManager->getRepository(EmployeeAccount::class);

            /**
             * @var EmployeeAccount $employeeAccount
             */
            $employeeAccount = $employeeAccountRepository->findOneBy(['employeeId' => $employeeId]);
            // $employeeAccount = $employeeAccountRepository->findOneByUid($uid);
            return $employeeAccount;
        }

        return null;
    }
}
