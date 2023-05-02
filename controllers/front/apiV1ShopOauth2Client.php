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

use PrestaShop\Module\PsAccounts\Controller\AbstractShopRestController;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\ForgetOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Domain\Account\Command\RegisterOauth2ClientCommand;
use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopOauth2ClientRequest;

class ps_AccountsApiV1ShopOauth2ClientModuleFrontController extends AbstractShopRestController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * ps_AccountsApiV1ShopOauth2ClientModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandBus = $this->module->getService(CommandBus::class);
    }

    /**
     * @param Shop $shop
     * @param UpdateShopOauth2ClientRequest $request
     *
     * @return array
     *
     * @throws Exception
     */
    public function update(Shop $shop, UpdateShopOauth2ClientRequest $request): array
    {
        $this->commandBus->handle(
            new RegisterOauth2ClientCommand(
                $request->client_id,
                $request->client_secret
            )
        );

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
    public function delete(Shop $shop, array $payload): array
    {
        $this->commandBus->handle(new ForgetOauth2ClientCommand());

        return [
            'success' => true,
            'message' => 'Oauth client deleted successfully',
        ];
    }
}
