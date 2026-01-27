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
        foreach (['with', 'get', 'reset'] as $prefix) {
            if (strpos($methodName, $prefix) === 0) {
                $property = $this->extractPropertyName($methodName, $prefix);

                return $this->{$prefix . 'Property'}($property, ...$args);
            }
        }
        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $methodName));
    }

    /**
     * Fluent setter
     *
     * @param string $property
     * @param null $value
     *
     * @return $this
     */
    public function withProperty($property, $value = null)
    {
        $this->$property = $value;

        return $this;
    }

    /**
     * Property getter
     *
     * @param string $property
     * @param bool $restoreDefault
     *
     * @return mixed|null
     */
    public function getProperty($property, $restoreDefault = true)
    {
        $value = $this->$property;

        if ($restoreDefault) {
            $this->resetProperty($property);
        }

        return $value;
    }

    /**
     * @param string $property
     *
     * @return void
     */
    public function resetProperty($property)
    {
        $defaults = $this->getDefaults();

        if (isset($defaults[$property])) {
            $this->$property = $defaults[$property];
        }
    }

    /**
     * @return void
     */
    protected function resetProperties()
    {
        foreach ($this->getDefaults() as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * @param string $methodName
     * @param string $prefix
     *
     * @return string
     */
    protected function extractPropertyName($methodName, $prefix)
    {
        $property = lcfirst(preg_replace('/^' . $prefix . '/', '', $methodName));

        if (empty($property) || !property_exists($this, $property)) {
            throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $property));
        }

        return $property;
    }
}
