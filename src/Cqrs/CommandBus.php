<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

class CommandBus extends AbstractBus
{
    /**
     * @param mixed $command
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function resolveHandler($command)
    {
        $commandClass = get_class($command);

        $handlerClass = preg_replace(
            '/((Command)(\\\\[^\\\\]*$))/',
            '${2}Handler${3}Handler',
            $commandClass, 1);

        return $this->module->getService($handlerClass);
    }
}
