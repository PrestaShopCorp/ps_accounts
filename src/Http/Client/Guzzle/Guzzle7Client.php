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

namespace PrestaShop\Module\PsAccounts\Http\Client\Guzzle;

use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Client;

class Guzzle7Client extends GuzzleClient
{
    public function __construct($options)
    {
        parent::__construct($options);

//        \Tools::refreshCACertFile();

        $this->client = new Client(array_merge(
            [
                'timeout' => $this->timeout,
                'http_errors' => $this->catchExceptions,
                'verify' => $this->getVerify(),
            ],
            $options
        ));
    }

    /**
     * @return bool|string
     */
    protected function getVerify()
    {
        if (version_compare((string) phpversion(), '7', '>=')) {
            /** @var \Ps_accounts $module */
            $module = \Module::getInstanceByName('ps_accounts');

            return (bool) $module->getParameter('ps_accounts.check_api_ssl_cert');
        }
        // bypass certificate expiration issue with PHP5.6
        return false;

//        if ((bool) $module->getParameter('ps_accounts.check_api_ssl_cert')) {
//            if (defined('_PS_CACHE_CA_CERT_FILE_') && file_exists(constant('_PS_CACHE_CA_CERT_FILE_'))) {
//                return constant('_PS_CACHE_CA_CERT_FILE_');
//            }
//
//            return true;
//        }
//        return false;
    }
}
