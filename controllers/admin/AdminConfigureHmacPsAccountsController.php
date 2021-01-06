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

use PrestaShop\AccountsAuth\DependencyInjection\PsAccountsServiceProvider;
use PrestaShop\AccountsAuth\Environment\Env;
use PrestaShop\AccountsAuth\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\AccountsAuth\Repository\ConfigurationRepository;
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
        $errorHandler = ErrorHandler::getInstance();

        try {
            $container = PsAccountsServiceProvider::getInstance();

            /** @var ConfigurationRepository $configuration */
            $configuration = $container->get(ConfigurationRepository::class);

            $container->get(Env::class);
            if (null === Tools::getValue('hmac')) {
                throw new HmacException('Hmac does not exist', 500);
            }
            $hmacPath = _PS_ROOT_DIR_ . '/upload/';
            foreach (['hmac' => '/[a-zA-Z0-9]{8,64}/', 'uid' => '/[a-zA-Z0-9]{8,64}/', 'slug' => '/[-_a-zA-Z0-9]{8,255}/'] as $key => $value) {
                if (!array_key_exists($key, Tools::getAllValues())) {
                    throw new QueryParamsException('Missing query params', 500);
                }

                if (!preg_match($value, Tools::getValue($key))) {
                    throw new QueryParamsException('Invalide query params', 500);
                }
            }

            if (!is_dir($hmacPath)) {
                mkdir($hmacPath);
            }

            if (!is_writable($hmacPath)) {
                throw new HmacException('Directory isn\'t writable', 500);
            }

            file_put_contents($hmacPath . Tools::getValue('uid') . '.txt', Tools::getValue('hmac'));

            $url = $_ENV['ACCOUNTS_SVC_UI_URL'];
            if (false === $url) {
                throw new EnvVarException('Environment variable ACCOUNTS_SVC_UI_URL should not be empty', 500);
            }

            if ('/' === substr($url, -1)) {
                $url = substr($url, 0, -1);
            }

            if (empty($configuration->getAccountsRsaSignData())) {
                throw new PsAccountsRsaSignDataEmptyException('PsAccounts RsaSignData couldn\'t be empty', 500);
            }

            Tools::redirect($url . '/shop/account/verify/' . Tools::getValue('uid')
            . '?shopKey='
            . urlencode($configuration->getAccountsRsaSignData()));
        } catch (Exception $e) {
            $errorHandler->handle($e, $e->getCode());
        }
    }
}
