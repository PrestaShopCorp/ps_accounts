<?php

namespace PrestaShop\Module\PsAccounts\Traits;

trait WithPropertyTrait
{
    /**
     * @return array
     */
    abstract public function getDefaults();

    /**
     * @param string $methodName
     * @param array $args
     *
     * @return mixed|void
     */
    public function __call($methodName, array $args)
    {
        $with = 'with';
        if (strpos($methodName, $with) === 0) {
            return $this->withProperty($with, $methodName, ...$args);
        }
        $get = 'get';
        if (strpos($methodName, $get) === 0) {
            return $this->getProperty($get, $methodName, ...$args);
        }
    }

    /**
     * @return void
     */
    protected function initDefaults()
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
    protected function restoreDefault($property)
    {
        $defaults = $this->getDefaults();

        if (isset($defaults[$property])) {
            $this->$property = $defaults[$property];
        }
    }

    /**
     * Fluent setter
     *
     * @param string $prefix
     * @param string $methodName
     * @param null $value
     *
     * @return $this
     */
    private function withProperty($prefix, $methodName, $value = null)
    {
        $property = $this->extractPropertyName($methodName, $prefix);

        $this->$property = $value;

        return $this;
    }

    /**
     * Property getter
     *
     * @param string $prefix
     * @param string $methodName
     * @param bool $restoreDefault
     *
     * @return mixed|null
     */
    private function getProperty($prefix, $methodName, $restoreDefault = true)
    {
        $property = $this->extractPropertyName($methodName, $prefix);

        $value = $this->$property;

        if ($restoreDefault) {
            $this->restoreDefault($property);
        }

        return $value;
    }

    /**
     * @param string $methodName
     * @param string $prefix
     *
     * @return string
     */
    public function extractPropertyName($methodName, $prefix)
    {
        $property = lcfirst(preg_replace('/^' . $prefix . '/', '', $methodName));

        if (empty($property) || !property_exists($this, $property)) {
            throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $property));
        }

        return $property;
    }
}
