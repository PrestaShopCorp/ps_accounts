<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Environment;

//use Symfony\Component\Dotenv\Dotenv;
use Dotenv\Dotenv;

/**
 * Load environment variables.
 */
class Env
{
    const MODULE_NAME = 'ps_accounts';

    /**
     * Const that define all environment possible to use.
     * Top of the list are taken in first if they exist in the project.
     * eg: If .env.test is present in the module it will be loaded, if not present
     * we try to load the next one etc ...
     *
     * @var array
     */
    const FILE_ENV_LIST = [
        'test' => '.env.test',
        'prod' => '.env',
    ];

    /**
     * Environment name: can be 'prod' or 'test'
     *
     * @var string
     */
    protected $name;

    /**
     * Environment mode: can be 'live' or 'sandbox'
     *
     * @var string
     */
    protected $mode;

    /**
     * Env constructor.
     */
    public function __construct()
    {
        foreach (self::FILE_ENV_LIST as $env => $fileName) {
            if (!file_exists(_PS_MODULE_DIR_ . self::MODULE_NAME . '/' . $fileName)) {
                continue;
            }

            $dotenv = Dotenv::create(_PS_MODULE_DIR_ . self::MODULE_NAME . '/', $fileName);
            $dotenv->load();

            $this->setName($env);

            break;
        }
    }

    /**
     * getName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * setName
     *
     * @param string $name
     *
     * @return void
     */
    private function setName($name)
    {
        $this->name = $name;
    }
}
