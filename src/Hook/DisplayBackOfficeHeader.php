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

namespace PrestaShop\Module\PsAccounts\Hook;

use Exception;
use PrestaShop\Module\PsAccounts\Account\Command\UpdateModuleCommand;
use PrestaShop\Module\PsAccounts\Account\Dto\UpdateModule;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Vendor\League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class DisplayBackOfficeHeader extends Hook
{
    /**
     * @var ConfigurationRepository
     */
    private $configRepo;

    /**
     * @var ShopSession
     */
    private $shopSession;

    public function __construct(\Ps_accounts $module)
    {
        parent::__construct($module);

        $this->shopSession = $this->module->getService(ShopSession::class);
        $this->configRepo = $this->module->getService(ConfigurationRepository::class);
    }

    /**
     * @return void
     *
     * @throws IdentityProviderException
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        $this->module->getOauth2Middleware()->execute();

        // TODO: update all shops at once ?
        // OAuthClient must be updated
        if ($this->configRepo->getFirebaseRefreshToken()) {
            // last call to refresh shop token & force null refreshToken
            $this->shopSession->setToken($this->getOrRefreshShopToken(), null);
            $this->commandBus->handle(new UpdateModuleCommand(new UpdateModule([
                'version' => \Ps_accounts::VERSION,
            ])));
        }
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    private function getOrRefreshShopToken()
    {
        $token = $this->shopSession->getToken();
        if ($token->isExpired()) {
            /** @var AccountsClient $accountsApi */
            $accountsApi = $this->module->getService(AccountsClient::class);
            $response = $accountsApi->refreshShopToken(
                $this->configRepo->getFirebaseRefreshToken(),
                $this->configRepo->getShopUuid()
            );

            if (isset($response['body']['token'])) {
                return $response['body']['token'];
            }
        }

        return (string) $token;
    }
}
