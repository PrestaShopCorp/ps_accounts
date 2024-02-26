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

use PrestaShop\Module\PsAccounts\Account\Command\DeleteUserShopCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\IndirectChannelClient;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\SentryService;

/**
 * Controller for all ajax calls.
 */
class AdminAjaxPsAccountsController extends \ModuleAdminController
{
    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandBus = $this->module->getService(CommandBus::class);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetOrRefreshToken()
    {
        try {
            /** @var ShopSession $shopSession */
            $shopSession = $this->module->getService(ShopSession::class);

            header('Content-Type: text/json');

            $token = $shopSession->getOrRefreshToken();

            $this->ajaxDie(
                json_encode([
                    'token' => (string) $token->getJwt(),
                    'refreshToken' => $token->getRefreshToken(),
                ])
            );
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    //public function displayAjaxUnlinkShop()
    public function ajaxProcessUnlinkShop()
    {
        try {
            /** @var ConfigurationRepository $configurationRepository */
            $configurationRepository = $this->module->getService(ConfigurationRepository::class);

            $response = $this->commandBus->handle(new DeleteUserShopCommand(
                $configurationRepository->getShopId()
            ));

            http_response_code($response['httpCode']);

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode($response['body']));
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessResetLinkAccount()
    {
        try {
            /** @var ConfigurationRepository $configurationRepository */
            $configurationRepository = $this->module->getService(ConfigurationRepository::class);

            $this->commandBus->handle(new UnlinkShopCommand(
                $configurationRepository->getShopId()
            ));

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode(['message' => 'success']));
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetContext()
    {
        try {
            $psxName = Tools::getValue('psx_name');

            /** @var PsAccountsPresenter $presenter */
            $presenter = $this->module->getService(PsAccountsPresenter::class);

            header('Content-Type: text/json');

            $this->ajaxDie(json_encode($presenter->present($psxName)));
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetOrRefreshAccessToken()
    {
        try {
            /** @var PrestaShopSession $oauthSession */
            $oauthSession = $this->module->getService(PrestaShopSession::class);

            header('Content-Type: text/json');

            $this->ajaxDie(
                json_encode([
                    'token' => (string) $oauthSession->getOrRefreshAccessToken(),
                ])
            );
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetInvitations()
    {
        try {
            header('Content-Type: text/json');
            $indirectsApi = $this->module->getService(
                IndirectChannelClient::class
            );
            $response = $indirectsApi->getInvitations();

            if (!$response || true !== $response['status']) {
                // TODO log error
                $this->ajaxDie(
                    json_encode([
                        'invitations' => [],
                    ])
                );
            } else {
                $this->ajaxDie(
                    json_encode([
                        'invitations' => $response['body']['invitations'],
                    ])
                );
            }
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
        }
    }
}
