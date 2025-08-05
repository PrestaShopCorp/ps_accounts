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
require_once __DIR__ . '/../../src/Polyfill/Traits/Controller/AjaxRender.php';

use PrestaShop\Module\PsAccounts\Account\Command\DeleteUserShopCommand;
use PrestaShop\Module\PsAccounts\Account\Query\GetContextQuery;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\AccountLogin\OAuth2Session;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Cqrs\QueryBus;
use PrestaShop\Module\PsAccounts\Hook\ActionShopAccountUnlinkAfter;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\AdminController\IsAnonymousAllowed;
use PrestaShop\Module\PsAccounts\Polyfill\Traits\Controller\AjaxRender;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\SentryService;

/**
 * Controller for all ajax calls.
 */
class AdminAjaxPsAccountsController extends \ModuleAdminController
{
    use AjaxRender;
    use IsAnonymousAllowed;

    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var QueryBus
     */
    private $queryBus;

    /**
     * AdminAjaxPsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandBus = $this->module->getService(CommandBus::class);
        $this->queryBus = $this->module->getService(QueryBus::class);

        $this->ajax = true;
        $this->content_only = true;
    }

    /**
     * @return bool
     */
    public function checkToken()
    {
        parent::checkToken();
        // TODO: check token ici
        return true;
    }

    /**
     * All BO users can access the login page
     *
     * @param bool $disable
     *
     * @return bool
     */
    /*public function viewAccess($disable = false)
    {
        return true;
    }*/

    /**
     * @return void|null
     *
     * @throws PrestaShopException
     */
//    public function init()
//    {
//        switch ($_SERVER['REQUEST_METHOD']) {
//            case 'OPTIONS':
//                // Handle OPTIONS request
//                header('Access-Control-Allow-Origin: *'); // TODO: filter Origin with authorized domains
//                header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
//                header('Access-Control-Allow-Headers: token, Content-Type');
//                header('Access-Control-Max-Age: 1728000');
//                header('Content-Length: 0');
//                header('Content-Type: text/plain');
//                die();
//
//        }
//        parent::init();
//    }

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

            $token = $shopSession->getValidToken();

            $this->ajaxRender(
                (string) json_encode([
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

            $this->ajaxRender((string) json_encode($response['body']));
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
            /** @var StatusManager $statusManager */
            $statusManager = $this->module->getService(StatusManager::class);

            $status = $statusManager->getStatus();

            $statusManager->invalidateCache();

            Hook::exec(ActionShopAccountUnlinkAfter::getName(), [
                'cloudShopId' => $status->cloudShopId,
                'shopId' => \Context::getContext()->shop->id,
            ]);

            header('Content-Type: text/json');

            $this->ajaxRender((string) json_encode(['message' => 'success']));
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
            /** @var OAuth2Session $oauth2Session */
            $oauth2Session = $this->module->getService(OAuth2Session::class);

            header('Content-Type: text/json');

            $this->ajaxRender(
                (string) json_encode([
                    'token' => (string) $oauth2Session->getOrRefreshAccessToken(),
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
    public function ajaxProcessGetContext()
    {
        //sleep(1);

        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('Content-Type: application/json');

        //header('Content-Type: text/json');

        try {
            $command = new GetContextQuery(
                Tools::getValue('group_id', null),
                Tools::getValue('shop_id', null),
                filter_var(Tools::getValue('refresh', false), FILTER_VALIDATE_BOOLEAN)
            );

            $this->ajaxRender(
                (string) json_encode($this->queryBus->handle($command))
            );
        } catch (Exception $e) {
            Logger::getInstance()->error($e->getMessage());

            http_response_code(500);

            $this->ajaxRender(
                (string) json_encode([
                    'error' => true,
                    'message' => $e->getMessage(),
                ])
            );
        }
    }
}
