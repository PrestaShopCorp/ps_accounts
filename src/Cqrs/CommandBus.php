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
     * @param mixed $command
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function resolveHandler($command)
    {
        $commandClass = get_class($command);

        $handlerClass = preg_replace(
            '/((Command|Query)(\\\\[^\\\\]*$))/',
            '${2}Handler${3}',
            $commandClass, 1);

        $handlerClass .= 'Handler';

        return $this->module->getService($handlerClass);
    }

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

        $handler = $this->resolveHandler($command);

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
