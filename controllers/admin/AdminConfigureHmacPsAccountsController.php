<?php
/**
* 2007-2020 PrestaShop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\Module\PsAccounts\DependencyInjection\PsAccountsServiceProvider;
use PrestaShop\Module\PsAccounts\Environment\Env;
use PrestaShop\Module\PsAccounts\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;
use PrestaShop\Module\PsAccounts\Exception\HmacException;
use PrestaShop\Module\PsAccounts\Exception\PsAccountsRsaSignDataEmptyException;
use PrestaShop\Module\PsAccounts\Exception\QueryParamsException;

/**
 * Controller generate hmac and redirect on hmac's file.
 */
class AdminConfigureHmacPsAccountsController extends ModuleAdminController
{
    /**
     * @return void
     *
     * @throws Exception
     */
    public function initContent()
    {
        $errorHandler = $this->module->getService(ErrorHandler::class);

        try {
            /** @var PsAccountsService $psAccountsService */
            $psAccountsService = $this->module->getService(PsAccountsService::class);

            Tools::redirect(
                $psAccountsService->generateVerifyLink(
                    Tools::getAllValues(),
                    _PS_ROOT_DIR_

                )
            );
        } catch (Exception $e) {
            $errorHandler->handle($e, $e->getCode());
        }
    }
}
