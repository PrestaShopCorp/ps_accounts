<?php

namespace PrestaShop\Module\PsAccounts\Polyfill;

use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class ConfigurationStorageSession
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var string
     */
    private $name = 'PS_ACCOUNTS_SESSION';

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $this->name .= '_' . \Context::getContext()->employee->id;
    }

    /**
     * Starts the session storage.
     *
     * @return bool True if session started
     *
     * @throws \RuntimeException if session fails to start
     */
    public function start()
    {
        if (!$this->getId()) {
            $this->cleanup();
            $this->setId(uniqid());
        }

        return true;
    }

    /**
     * Returns the session ID.
     *
     * @return string The session ID
     */
    public function getId()
    {
        return \Context::getContext()->cookie->id_fallback_session;
    }

    /**
     * Sets the session ID.
     *
     * @param string $id
     */
    public function setId($id)
    {
        \Context::getContext()->cookie->id_fallback_session = $id;
    }

    /**
     * Returns the session name.
     *
     * @return mixed The session name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the session name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Invalidates the current session.
     *
     * Clears all session attributes and flashes and regenerates the
     * session and deletes the old session from persistence.
     *
     * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     *
     * @return bool True if session invalidated, false if error
     */
    public function invalidate($lifetime = null)
    {
        $this->notImplemented();
    }

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     *
     * @param bool $destroy Whether to delete the old session or leave it to garbage collection
     * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                      will leave the system settings unchanged, 0 sets the cookie
     *                      to expire with browser session. Time is in seconds, and is
     *                      not a Unix timestamp.
     *
     * @return bool True if session migrated, false if error
     */
    public function migrate($destroy = false, $lifetime = null)
    {
        $this->notImplemented();
    }

    /**
     * Force the session to be saved and closed.
     *
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save()
    {
    }

    /**
     * Checks if an attribute is defined.
     *
     * @param string $name The attribute name
     *
     * @return bool true if the attribute is defined, false otherwise
     */
    public function has($name)
    {
        return array_key_exists($name, $this->all());
    }

    /**
     * Returns an attribute.
     *
     * @param string $name The attribute name
     * @param mixed $default The default value if not found
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $session = $this->all();

        return array_key_exists($name, $session) ? $session[$name] : $default;
    }

    /**
     * Sets an attribute.
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $session = $this->all();
        $session[$name] = $value;

        $this->configuration->set($this->getConfigurationName(), json_encode($session));
    }

    /**
     * Returns attributes.
     *
     * @return array Attributes
     */
    public function all()
    {
        $all = json_decode($this->configuration->get($this->getConfigurationName(), false, false), true);

        if (is_array($all)) {
            return $all;
        }

        return [];
    }

    /**
     * Sets attributes.
     *
     * @param array $attributes Attributes
     */
    public function replace(array $attributes)
    {
        $this->notImplemented();
    }

    /**
     * Removes an attribute.
     *
     * @param string $name
     *
     * @return mixed The removed value or null when it does not exist
     */
    public function remove($name)
    {
        $session = $this->all();
        unset($session[$name]);

        $this->configuration->set($name, json_encode($session));
    }

    /**
     * Clears all attributes.
     */
    public function clear()
    {
        $this->configuration->set($this->getConfigurationName(), json_encode([]));
    }

    /**
     * Checks if the session was started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return !empty($this->getId());
    }

    /**
     * Registers a SessionBagInterface with the session.
     */
    public function registerBag(SessionBagInterface $bag)
    {
        $this->notImplemented();
    }

    /**
     * Gets a bag instance by name.
     *
     * @param string $name
     *
     * @return SessionBagInterface
     */
    public function getBag($name)
    {
        $this->notImplemented();
    }

    /**
     * Gets session meta.
     *
     * @return MetadataBag
     */
    public function getMetadataBag()
    {
        $this->notImplemented();
    }

    /**
     * @return string
     */
    private function getConfigurationName()
    {
        return $this->getName() . '_' . $this->getId();
    }

    /**
     * @return void
     */
    private function cleanup()
    {
        \Db::getInstance()->query(
            'DELETE FROM ' . _DB_PREFIX_ . "configuration WHERE name LIKE '" . $this->name . "_%'"
        );
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    private function notImplemented()
    {
        throw new \Exception('Method not implemented : ' . __METHOD__);
    }
}
