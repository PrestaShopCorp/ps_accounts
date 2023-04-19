<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

class CommandBus
{
    /**
     * @var \Ps_accounts
     */
    protected $module;

    public function __construct(\Ps_accounts $module)
    {
        $this->module = $module;
    }

    /**
     * @throws \Exception
     */
    public function resolveHandler($command)
    {
        $handlerClass = preg_replace('/(Command|Query)?$/', 'Handler', get_class($command));

        return $this->module->getService($handlerClass);
    }

    /**
     * @throws \Exception
     */
    public function execute($command)
    {
        $this->module->getLogger()->debug('handling command : ' . get_class($command));

        $handler = $this->resolveHandler($command);

        if (method_exists($handler, 'handle')) {
            return $handler->handle($command);
        }
        throw new \Exception('handle method not found');
    }
}
