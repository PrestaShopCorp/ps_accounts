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
            return $this->withProperty($methodName, $args, $with);
        }
        $get = 'get';
        if (strpos($methodName, $get) === 0) {
            return $this->getAndRestoreProperty($methodName, $get);
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
     * @param string $methodName
     * @param array $args
     * @param string $prefix
     *
     * @return $this
     */
    private function withProperty($methodName, array $args, $prefix = 'with')
    {
        $property = $this->extractPropertyName($methodName, $prefix);

        $value = isset($args[0]) ? $args[0] : null;

        $this->$property = $value;

        return $this;
    }

    /**
     * Property getter
     *
     * @param string $methodName
     * @param string $prefix
     *
     * @return mixed|null
     */
    private function getAndRestoreProperty($methodName, $prefix = 'get')
    {
        $property = $this->extractPropertyName($methodName, $prefix);

        $value = $this->$property;

        $this->restoreDefault($property);

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
