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
use PrestaShop\Module\PsAccounts\Account\Command\DeleteUserShopCommand;
use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Client\IndirectChannelClient;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Provider\OAuth2\PrestaShopSession;
=======
use Account\Query\GetOrRefreshAccessToken;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Cqrs\QueryBus;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\DeleteUserShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Association;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\Token;
use PrestaShop\Module\PsAccounts\Domain\Shop\Query\GetOrRefreshShopToken;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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
<<<<<<< HEAD
=======
     * @var QueryBus
     */
    private $queryBus;

    /**
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

<<<<<<< HEAD
=======
        $this->queryBus = $this->module->getService(QueryBus::class);
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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
<<<<<<< HEAD
            /** @var ShopSession $shopSession */
            $shopSession = $this->module->getService(ShopSession::class);
=======
            /** @var Token $token */
            $token = $this->queryBus->handle(new GetOrRefreshShopToken());
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

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
<<<<<<< HEAD
            /** @var ConfigurationRepository $configurationRepository */
            $configurationRepository = $this->module->getService(ConfigurationRepository::class);

            $this->commandBus->handle(new UnlinkShopCommand(
                $configurationRepository->getShopId()
            ));
=======
            /** @var Association $association */
            $association = $this->module->getService(Association::class);

            $association->resetLink();
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)

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
            header('Content-Type: text/json');

            $this->ajaxDie(
                json_encode([
                    'token' => (string) $this->queryBus->handle(
                        new GetOrRefreshAccessToken()
                    ),
                ])
            );
        } catch (Exception $e) {
            SentryService::captureAndRethrow($e);
<<<<<<< HEAD
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
=======
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
        }
    }
}
