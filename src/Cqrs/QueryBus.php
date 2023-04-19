<?php

namespace PrestaShop\Module\PsAccounts\Cqrs;

class QueryBus extends CommandBus
{
    public function execute($query)
    {
        $this->module->getLogger()->debug('handling query : ' . get_class($query));

        return parent::execute($query);
    }
}
