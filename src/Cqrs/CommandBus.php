<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

class CommandBus extends AbstractBus
{
    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveHandlerClass(string $className): string
    {
        return preg_replace(
            '/((Command)(\\\\([^\\\\]*?)(Command)?$))/',
            '${2}Handler\\\\${4}Handler',
            $className, 1);
    }
}
