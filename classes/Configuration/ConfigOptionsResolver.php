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

namespace PrestaShop\Module\PsAccounts\Configuration;

use PrestaShop\Module\PsAccounts\Exception\OptionResolutionException;

class ConfigOptionsResolver
{
    /**
     * @var array
     */
    private $required;

    /**
     * @var array
     */
    private $defaults;

    public function __construct(array $required, array $defaults = [])
    {
        $this->required = $required;

        $this->defaults = $defaults;
    }

    /**
     * @param array $config
     * @param array $defaults
     *
     * @return array
     *
     * @throws OptionResolutionException
     */
    public function resolve(array $config, array $defaults = [])
    {
        $defaults = array_merge($this->defaults, $defaults);

        $diff = array_diff($this->required, array_keys($config), array_keys($defaults));

        if (count($diff) > 0) {
            throw new OptionResolutionException('Configuration option missing : [' . array_shift($diff) . ']');
        }

        $config = array_merge($defaults, $config);

        //error_log('#### config ' . print_r($config, true));

        return $config;
    }
}
