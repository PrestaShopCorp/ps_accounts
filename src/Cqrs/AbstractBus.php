<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

abstract class AbstractBus
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
     * @param string $className
     *
     * @return string
     */
    abstract public function resolveHandlerClass(string $className): string;

    /**
     * @param mixed $command
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function handle($command)
    {
        $this->module->getLogger()->debug('resolving handler : ' . get_class($command));

        $handler = $this->module->getService($this->resolveHandlerClass(get_class($command)));

        if ($handler && method_exists($handler, 'handle')) {
            /* @phpstan-ignore-next-line */
            $this->module->getLogger()->debug('handling : ' . get_class($handler));
            $this->module->getLogger()->debug('with data : ' . json_encode($command));

            /* @phpstan-ignore-next-line */
            return $handler->handle($command);
        }
        throw new \Exception('handle method not found');
    }
}
