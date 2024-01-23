<?php

namespace PrestaShop\Module\PsAccounts\Hook;

trait TriggerHooksTrait
{
    /**
     * @param string $methodName
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function executeHook($methodName, array $params = [])
    {
        if (strpos($methodName, 'hook') === 0) {
            $class = '\PrestaShop\Module\PsAccounts\Hook\\' .
                ucfirst(preg_replace('/^hook/', '', $methodName));

//            $this->getLogger()->error('# ' . $class);

            if (class_exists($class)) {
//                $this->getLogger()->error('#2 ' . $class);
                /** @var \PrestaShop\Module\PsAccounts\Hook\BaseHook $hook */
                $hook = (new $class($this));
                return $hook->execute($params);
            }
        }
        return null;
    }

    public function __call($name, $arguments)
    {
        $this->executeHook($name, $arguments[0]);
    }
}
