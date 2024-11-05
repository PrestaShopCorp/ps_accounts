<?php

namespace PrestaShop\Module\PsAccounts\ServiceContainer;

interface IServiceContainerService
{
    /**
     * @param ServiceContainer $serviceContainer
     *
     * @return mixed
     */
    public static function getInstance(ServiceContainer $serviceContainer);
}
