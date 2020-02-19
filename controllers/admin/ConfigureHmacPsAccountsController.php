<?php
/**
* 2007-2019 PrestaShop.
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
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
class ConfigureHmacPsAccountsController extends ModuleAdminController
{
    /**
     * Construct.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Initialize the content by adding Boostrap and loading the TPL.
     *
     * @param none
     *
     * @return none
     */
    public function initContent()
    {
        try {
            $queryParams = $_GET;

            $hmacPath = dirname(__FILE__).'/../../../../upload/';
            foreach (['hmac', 'uid', 'slug'] as $key) {
                if (!array_key_exists($key, $queryParams)) {
                    throw new Exception("Missing query params \n");
                }
            }

            if (!is_dir($hmacPath)) {
                mkdir($hmacPath);
                echo 'Directory created';
            }
            if (null === $queryParams['hmac']) {
                throw new Exception("Caught exception: Hmac does not exist \n");
            }

            file_put_contents($hmacPath.$queryParams['uid'].'.txt', $queryParams['hmac']);
        } catch (Exception $e) {
            var_dump($e);
            die;
        }
        $url = $_ENV['SSO_URL'].'/verify-shop/'.$queryParams['uid'].'?hmacPath='.urlencode(
            '/upload/'.$queryParams['uid'].'.txt&shopKey='.substr(
                Configuration::get('PS_ACCOUNTS_RSA_SIGN_DATA'),
                0,
                16
            )
        );

        header('Location: '.$url);
    }
}
