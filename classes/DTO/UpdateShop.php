<?php

namespace PrestaShop\Module\PsAccounts\DTO;

class UpdateShop extends AbstractDto
{
    /**
     * @var
     */
    public $shopId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $virtualUri;

    /**
     * @var string
     */
    public $physicalUri;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var string
     */
    public $sslDomain;

    /**
     * @var string
     */
    public $boBaseUrl;

    /**
     * @var string[]
     */
    public $mandatory = [
        'shopId',
        'name',
        'virtualUri',
        'physicalUri',
        'domain',
        'sslDomain',
        'boBaseUrl',
    ];
}
