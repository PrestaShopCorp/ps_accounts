<?php

namespace PrestaShop\Module\PsAccounts;

use PrestaShop\Module\PsAccounts\Log\Logger;

trait WithTrait
{
    /**
     * @param string $methodName
     * @param mixed $args
     *
     * @return mixed|void
     */
    private function callWith($methodName, ...$args)
    {
        if (strpos($methodName, 'with') === 0) {
            $property = lcfirst(preg_replace('/^with/', '', $methodName));

            $value = isset($args[0]) ? $args[0] : null;

            if (!empty($property) && property_exists($this, $property)) {
                $this->$property = $value;
            }

            return $this;
        }
        if (strpos($methodName, 'get') === 0) {
            $property = lcfirst(preg_replace('/^get/', '', $methodName));

            if (property_exists($this, $property)) {

                $value = $this->$property;

                $this->restoreDefault($property);

                return $value;
            }
        }
    }

    /**
     * @return array
     */
    abstract public function getDefaults();

    /**
     * @return void
     */
    public function initDefaults()
    {
        foreach ($this->getDefaults() as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * @param string $property
     *
     * @return void
     */
    public function restoreDefault($property)
    {
        $defaults = $this->getDefaults();

        if (isset($defaults[$property])) {
            $this->$property = $defaults[$property];
        }
    }

    /**
     * @param string $name
     * @param array $args
     *
     * @return self
     */
    public function __call($name, array $args)
    {
        return $this->callWith($name, ...$args);
    }
}
