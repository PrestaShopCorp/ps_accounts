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

namespace PrestaShop\Module\PsAccounts\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UpgradeModuleCommand;
use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Api\Client\AccountsClient;
use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class UpgradeModuleHandler
{
    /**
     * @var LinkShop
     */
    private $linkShop;

    /**
     * @var ShopSession
     */
    private $shopSession;

    /**
     * @var AccountsClient
     */
    private $accountsClient;

    /**
     * @var ConfigurationRepository
     */
    private $configRepo;

    /**
     * @var ShopContext
     */
    private $shopContext;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        AccountsClient $accountsClient,
        LinkShop $linkShop,
        ShopSession $shopSession,
        ShopContext $shopContext,
        ConfigurationRepository $configurationRepository,
        CommandBus $commandBus
    ) {
        $this->accountsClient = $accountsClient;
        $this->linkShop = $linkShop;
        $this->shopSession = $shopSession;
        $this->shopContext = $shopContext;
        $this->configRepo = $configurationRepository;
        $this->commandBus = $commandBus;
    }

    /**
     * @param UpgradeModuleCommand $command
     *
     * @return void
     *
     * @throws RefreshTokenException
     */
    public function handle(UpgradeModuleCommand $command)
    {
        $this->shopContext->execInShopContext($command->payload->shopId, function () use ($command) {
            $lastUpgrade = $this->configRepo->getLastUpgrade(false);

            if (version_compare($lastUpgrade, $command->payload->version, '<')) {
                Logger::getInstance()->info(
                    'attempt upgrade [' . $lastUpgrade . ' to ' . $command->payload->version . ']'
                );

                // Set new version a soon as we can to avoid duplicate calls
                $this->configRepo->updateLastUpgrade($command->payload->version);

                // FIXME: to be removed once oauth client has been updated
                //if (version_compare($lastUpgrade, '7.0.0', '<')) {
                $this->lastChanceToRefreshShopToken();
                //}

                $token = $this->shopSession->getValidToken();

                if (!$token->getJwt() instanceof NullToken) {
                    $response = $this->accountsClient->upgradeShopModule(
                        $this->linkShop->getShopUuid(),
                        (string) $token,
                        $command->payload
                    );

                    if (!$response['status']) {
                        $this->commandBus->handle(new UnlinkShopCommand(
                            $this->configRepo->getShopId(),
                            $response['httpCode']
                        ));
                    }
                }
            }
        });
    }

    /**
     * @return array
     */
    private function getOrRefreshShopToken()
    {
        $token = $this->shopSession->getToken();
        if ($token->isExpired() && !empty($token->getRefreshToken())) {
            $response = $this->accountsClient->refreshShopToken(
                //$this->configRepo->getFirebaseRefreshToken(),
                $token->getRefreshToken(),
                //$this->configRepo->getShopUuid()
                $this->linkShop->getShopUuid()
            );

            if (isset($response['body']['token'])) {
                return [
                    'token' => $response['body']['token'],
                    'refresh_token' => $response['body']['refresh_token'],
                ];
            }
        }

        return [
            'token' => (string) $token,
            'refresh_token' => $token->getRefreshToken(),
        ];
    }

    /**
     * @return void
     */
    private function lastChanceToRefreshShopToken()
    {
        $tokens = $this->getOrRefreshShopToken();
        $this->shopSession->setToken(
            $tokens['token'],
            $tokens['refresh_token']
        );
    }
}
