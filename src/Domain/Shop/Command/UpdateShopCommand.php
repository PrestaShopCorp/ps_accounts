<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Dto\UpdateShop as UpdateShopDto;

class UpdateShopCommand
{
    /**
     * @var UpdateShopDto
     */
    public $payload;

    public function __construct(UpdateShopDto $payload)
    {
        $this->payload = $payload;

        $this->payload->domain = $this->enforceHttpScheme($this->payload->domain, false);
        $this->payload->sslDomain = $this->enforceHttpScheme($this->payload->sslDomain);
    }

    public function enforceHttpScheme($url, $https = true)
    {
        $scheme = 'http' . ($https ? 's' : '') . '://';
        return preg_replace(
            "/^(\w+:\/\/|)/",
            $scheme,
            $this->payload->sslDomain
        );
    }


}
