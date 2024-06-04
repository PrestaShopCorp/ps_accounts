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
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Api\Controller\Request\UpdateShopOauth2ClientRequest;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
=======
use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\RegisterOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopOauth2ClientRequest;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

class ps_AccountsApiV1ShopOauth2ClientModuleFrontController extends AbstractShopRestController
{
    /**
<<<<<<< HEAD
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * @var ShopSession
     */
    private $session;
=======
     * @var CommandBus
     */
    private $commandBus;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

    /**
     * ps_AccountsApiV1ShopOauth2ClientModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

<<<<<<< HEAD
        $this->oauth2Client = $this->module->getService(Oauth2Client::class);
        $this->session = $this->module->getService(ShopSession::class);
=======
        $this->commandBus = $this->module->getService(CommandBus::class);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
    }

    /**
     * @param Shop $shop
     * @param UpdateShopOauth2ClientRequest $request
     *
     * @return array
     *
     * @throws Exception
     */
    public function update(Shop $shop, UpdateShopOauth2ClientRequest $request)
    {
<<<<<<< HEAD
        $this->oauth2Client->update($request->client_id, $request->client_secret);
        $this->session->getOrRefreshToken();
=======
        $this->commandBus->handle(
            new RegisterOauth2ClientCommand(
                $request->client_id,
                $request->client_secret
            )
        );
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        return [
            'success' => true,
            'message' => 'Oauth client stored successfully',
        ];
    }

    /**
     * @param Shop $shop
     * @param array $payload
     *
     * @return array
     *
     * @throws Exception
     */
    public function delete(Shop $shop, array $payload)
    {
<<<<<<< HEAD
        $this->oauth2Client->delete();
=======
        $this->commandBus->handle(new ForgetOauth2ClientCommand());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

        return [
            'success' => true,
            'message' => 'Oauth client deleted successfully',
        ];
    }
}
