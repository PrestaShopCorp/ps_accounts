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

use PrestaShop\Module\PsAccounts\Adapter\Configuration;

/**
 * @deprecated
 *
 * @todo Not production ready for ps1.6
 */
class FallbackSession implements Session
{
    const SESSION_PREFIX = 'SESSION';
    const SESSION_NAME = '_pssesid';

    /**
     * @var int
     */
    private $gcLifetimeSeconds;

    /**
     * @var Configuration
     */
    private $configStorage;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @param Configuration $configStorage
     * @param int $gcLifetimeSeconds
     */
    public function __construct(Configuration $configStorage, $gcLifetimeSeconds = 3600)
    {
        $this->configStorage = $configStorage;
        $this->gcLifetimeSeconds = $gcLifetimeSeconds;
        $this->sessionId = $this->startSession();
        $this->gcClear();
    }

    /**
     * @return bool
     */
    public function start()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return '';
    }

    /**
     * @param string $id
     *
     * @return void
     */
    public function setId($id)
    {
        // TODO: Implement setId() method.
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::SESSION_NAME;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName($name)
    {
        // TODO: Implement setName() method.
    }

    /**
     * @param int $lifetime
     *
     * @return bool
     */
    public function invalidate($lifetime = null)
    {
        return true;
    }

    /**
     * @param bool $destroy
     * @param int $lifetime
     *
     * @return bool
     */
    public function migrate($destroy = false, $lifetime = null)
    {
        return true;
    }

    /**
     * @return void
     */
    public function save()
    {
        // TODO: Implement save() method.
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return (bool) $this->configStorage->get($this->generateName($name));
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $value = unserialize($this->configStorage->get($this->generateName($name), $default));
        //Logger::getInstance()->error('## GET ' . $this->generateName($name) . ':[' . $value .']');
        return $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function set($name, $value)
    {
        $this->configStorage->set($this->generateName($name), serialize($value));
    }

    /**
     * @return array
     */
    public function all()
    {
        // TODO: Implement all() method.
        return [];
    }

    /**
     * @param array $attributes
     *
     * @return void
     */
    public function replace(array $attributes)
    {
        // TODO: Implement replace() method.
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function remove($name)
    {
        $this->configStorage->set($this->generateName($name), '');
        // FIXME: return removed value
        return null;
    }

    /**
     * @return void
     */
    public function clear()
    {
        \Db::getInstance()->execute(
            'DELETE FROM ' . _DB_PREFIX_ . 'configuration ' .
            "WHERE name like '" . $this->generateName('') . "%'"
        );
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

//    public function registerBag(SessionBagInterface $bag)
//    {
//        // TODO: Implement registerBag() method.
//    }
//
//    public function getBag($name)
//    {
//        // TODO: Implement getBag() method.
//    }
//
//    public function getMetadataBag()
//    {
//        // TODO: Implement getMetadataBag() method.
//    }

    /**
     * @return void
     */
    protected function gcClear()
    {
        $datetime = (new \DateTime("-{$this->gcLifetimeSeconds} seconds"))->format('c');
        \Db::getInstance()->execute(
            'DELETE FROM ' . _DB_PREFIX_ . 'configuration ' .
            "WHERE name like '" . self::SESSION_PREFIX . "_%' " .
            "AND date_upd < '{$datetime}'"
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function generateName($name)
    {
        return self::SESSION_PREFIX . '_' . $this->sessionId . '_' . $name;
    }

    /**
     * @return mixed|string
     */
    private function startSession()
    {
        if (!isset($_COOKIE[self::SESSION_NAME])) {
            // Session Cookie
            setcookie(self::SESSION_NAME, uniqid());
        }

        return $_COOKIE[self::SESSION_NAME];
    }
}
