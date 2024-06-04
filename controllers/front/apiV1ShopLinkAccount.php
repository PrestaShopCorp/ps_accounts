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

<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Account\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\Dto\LinkShop;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Api\Controller\Request\UpdateShopLinkAccountRequest;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
=======
use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Dto\LinkShop;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopLinkAccountRequest;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

class ps_AccountsApiV1ShopLinkAccountModuleFrontController extends AbstractShopRestController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
<<<<<<< HEAD
=======
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var OwnerSession
     */
    private $ownerSession;

    /**
     * ps_AccountsApiV1ShopLinkAccountModuleFrontController constructor.
     *
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandBus = $this->module->getService(CommandBus::class);
        //$this->commandBus = $this->module->getContainer()->get('prestashop.command_bus');
<<<<<<< HEAD
    }

    /**
     * @param Shop $shop
     * @param UpdateShopLinkAccountRequest $request
     *
     * @return array
     *
=======
        $this->shopSession = $this->module->getService(ShopSession::class);
        $this->ownerSession = $this->module->getService(OwnerSession::class);
    }

    /**
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @throws RefreshTokenException
     * @throws Exception
     */
    public function update(Shop $shop, UpdateShopLinkAccountRequest $request)
    {
<<<<<<< HEAD
        $this->commandBus->handle(new LinkShopCommand(
            new LinkShop([
                'shopId' => $request->shop_id,
                'uid' => $request->uid,
                'ownerUid' => $request->owner_uid,
                'ownerEmail' => $request->owner_email,
=======
        $shopToken = $request->shop_token;
        $userToken = $request->user_token;

        if ($this->module->getParameter('ps_accounts.verify_account_tokens')) {
            if (false === $this->shopSession->verifyToken($shopToken)) {
                $shopToken = $this->shopSession->refreshToken($request->shop_refresh_token);
            }
            if (false === $this->ownerSession->verifyToken($userToken)) {
                $userToken = $this->ownerSession->refreshToken($request->user_refresh_token);
            }
        }

        $this->commandBus->handle(new LinkShopCommand(
            new LinkShop([
                'shopId' => $request->shop_id,
                'shopToken' => $shopToken,
                'userToken' => $userToken,
                'shopRefreshToken' => $request->shop_refresh_token,
                'userRefreshToken' => $request->user_refresh_token,
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
                'employeeId' => $request->employee_id,
            ])
        ));

        return [
            'success' => true,
            'message' => 'Link Account stored successfully',
        ];
    }

    /**
<<<<<<< HEAD
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     *
     * @throws PrestaShopException
=======
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * @throws Exception
     */
    public function delete(Shop $shop, array $payload)
    {
        $this->commandBus->handle(new UnlinkShopCommand($shop->id));

        return [
            'success' => true,
            'message' => 'Link Account deleted successfully',
        ];
    }
}
