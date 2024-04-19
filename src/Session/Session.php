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

namespace PrestaShop\Module\PsAccounts\Session;

interface Session
{
    /**
     * @return bool
     */
    public function start();

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $id
     *
     * @return void
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName($name);

    /**
     * @param int $lifetime
     *
     * @return bool
     */
    public function invalidate($lifetime = null);

    /**
     * @param bool $destroy
     * @param int $lifetime
     *
     * @return bool
     */
    public function migrate($destroy = false, $lifetime = null);

    /**
     * @return void
     */
    public function save();

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function set($name, $value);

    /**
     * @return array
     */
    public function all();

    /**
     * @param array $attributes
     *
     * @return void
     */
    public function replace(array $attributes);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function remove($name);

    /**
     * @return void
     */
    public function clear();

    /**
     * @return bool
     */
    public function isStarted();
}
