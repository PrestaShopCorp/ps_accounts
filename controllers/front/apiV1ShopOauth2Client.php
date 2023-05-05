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
use PrestaShop\Module\PsAccounts\DTO\Api\UpdateShopOauth2ClientRequest;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\Oauth2Client;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ps_AccountsApiV1ShopOauth2ClientModuleFrontController extends AbstractShopRestController
{
    /**
     * @var ConfigurationRepository
     */
    private $configuration;

    /**
     * @var Oauth2Client
     */
    private $oauth2Client;

    /**
     * ps_AccountsApiV1ShopOauth2ClientModuleFrontController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = $this->module->getService(ConfigurationRepository::class);
        $this->oauth2Client = $this->module->getService(Oauth2Client::class);
    }

    /**
     * @param Shop $shop
     * @param UpdateShopOauth2ClientRequest $request
     *
     * @return array
     */
    public function update(Shop $shop, UpdateShopOauth2ClientRequest $request): array
    {
        $this->oauth2Client->update($request->client_id, $request->client_secret);
        $this->configuration->updateLoginEnabled(true);

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
        $this->oauth2Client->delete();

        return [
            'success' => true,
            'message' => 'Oauth client deleted successfully',
        ];
    }
}
