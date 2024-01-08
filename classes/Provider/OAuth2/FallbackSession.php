<?php

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;
use PrestaShop\Module\PsAccounts\Log\Logger;

class FallbackSession
{
    const SESSION_PREFIX = 'SESSION';
    const SESSION_NAME = '_pssesid';

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
     */
    public function __construct(Configuration $configStorage)
    {
        $this->configStorage = $configStorage;
        $this->sessionId = $this->startSession();
    }

    public function start()
    {
        // TODO: Implement start() method.
    }

    public function getId()
    {
        // TODO: Implement getId() method.
    }

    public function setId($id)
    {
        // TODO: Implement setId() method.
    }

    public function getName()
    {
        // TODO: Implement getName() method.
    }

    public function setName($name)
    {
        // TODO: Implement setName() method.
    }

    public function invalidate($lifetime = null)
    {
        // TODO: Implement invalidate() method.
    }

    public function migrate($destroy = false, $lifetime = null)
    {
        // TODO: Implement migrate() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

    public function has($name)
    {
        return (bool)$this->configStorage->get($this->generateName($name));
    }

    public function get($name, $default = null)
    {
        $value = unserialize($this->configStorage->get($this->generateName($name), $default));
        //Logger::getInstance()->error('## GET ' . $this->generateName($name) . ':[' . $value .']');
        return $value;
    }

    public function set($name, $value)
    {
        $this->configStorage->set($this->generateName($name), serialize($value));
    }

    public function all()
    {
        // TODO: Implement all() method.
    }

    public function replace(array $attributes)
    {
        // TODO: Implement replace() method.
    }

    public function remove($name)
    {
        $this->configStorage->set($this->generateName($name), '');
    }

    public function clear()
    {
        \Db::getInstance()->execute(
            "DELETE FROM ps_configuration WHERE name like 'SESSION_" .
            $this->sessionId .
            "_%'"
        );
    }

    public function isStarted()
    {
        // TODO: Implement isStarted() method.
    }

//    public function registerBag(SessionBagInterface $bag)
//    {
//        // TODO: Implement registerBag() method.
//    }

    public function getBag($name)
    {
        // TODO: Implement getBag() method.
    }

    public function getMetadataBag()
    {
        // TODO: Implement getMetadataBag() method.
    }

    /**
     * @param $name
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