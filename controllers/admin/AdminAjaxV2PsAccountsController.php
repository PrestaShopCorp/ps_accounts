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
require_once __DIR__ . '/../../src/Http/Controller/AbstractAdminAjaxController.php';

use PrestaShop\Module\PsAccounts\Account\Command\MigrateOrCreateIdentityV8Command;
use PrestaShop\Module\PsAccounts\Account\Query\GetContextQuery;
use PrestaShop\Module\PsAccounts\Cqrs\CommandBus;
use PrestaShop\Module\PsAccounts\Cqrs\QueryBus;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractAdminAjaxController;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;

/**
 * Controller for all ajax calls.
 */
class AdminAjaxV2PsAccountsController extends AbstractAdminAjaxController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var QueryBus
     */
    private $queryBus;

    /**
     * AdminAjaxV2PsAccountsController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->commandBus = $this->module->getService(CommandBus::class);
        $this->queryBus = $this->module->getService(QueryBus::class);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessGetContext()
    {
        $command = new GetContextQuery(
            Tools::getValue('context_type', null),
            Tools::getValue('context_id', null),
            filter_var(Tools::getValue('refresh', false), FILTER_VALIDATE_BOOLEAN)
        );

        $this->ajaxRender(
            (string) json_encode($this->queryBus->handle($command))
        );
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function ajaxProcessFallbackCreateIdentity()
    {
        $shopId = Tools::getValue('shop_id', null);

        if (!$shopId) {
            throw new Exception('Shop ID is required for migration or creation.');
        }

        $command = new MigrateOrCreateIdentityV8Command($shopId);

        $this->commandBus->handle($command);

        $this->ajaxRender(
            (string) json_encode([
                'success' => true,
            ])
        );
    }

    /**
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    protected function handleError($e)
    {
        Logger::getInstance()->error($e);

        if ($e instanceof AccountsException) {
            http_response_code(400);

            $this->ajaxRender(
                (string) json_encode([
                    'message' => $e->getMessage(),
                    'code' => $e->getErrorCode(),
                ])
            );

            return;
        }

        parent::handleError($e);
    }
}
