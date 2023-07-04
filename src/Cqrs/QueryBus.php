<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

class QueryBus extends AbstractBus
{
    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveHandlerClass(string $className): string
    {
        return preg_replace(
            '/((Query)(\\\\([^\\\\]*?)(Query)?$))/',
            '${2}Handler\\\\${4}Handler',
            $className, 1);
    }
}
