<?php

namespace PrestaShop\Module\PsAccounts\Identity\Domain;

use Error;

class Identity
{
    /**
     * @var string
     */
	private $shopId;

    /**
     * @var string|null
     */
	private $cloudShopId;

    /**
     * @var Oauth2Client|null
     */
	private $oauth2Client;

    /**
     * Identity constructor
     *
     * @param string $shopId
     * @param string|null $cloudShopId
     * @param Oauth2Client|null $oauth2Client
     */
	public function __construct($shopId, $cloudShopId = null, Oauth2Client $oauth2Client = null)
    {
		$this->shopId = $shopId;
		$this->cloudShopId = $cloudShopId;
		$this->oauth2Client = $oauth2Client;
	}

    /**
     * @return void
     */
	public function create($shopId, $cloudShopId, Oauth2Client $oauth2Client)
	{
        if ($this->cloudShopId && $this->hasOAuth2Client()) {
            throw new Error('The store already have an identity');
        }

		$this->shopId = $shopId;
		$this->cloudShopId = $cloudShopId;
		$this->oauth2Client = $oauth2Client;

		// $this->record(new IdentityCreated($this->id, $this->oauth2Client));
	}

    /**
     * @return void
     */
	public function verify()
	{
        if (!$this->cloudShopId || !$this->hasOAuth2Client()) {
            throw new Error('The store does not have an identity');
        }

		// $this->record(new IdentityVerified($this->id));
	}

    /**
     * @return string
     */
	public function shopId()
	{
		return $this->shopId;
	}

    /**
     * @return string
     */
	public function cloudShopId()
	{
		return $this->cloudShopId;
	}

    /**
     * @return OAuth2Client
     */
	public function oauth2Client()
	{
		return $this->oauth2Client;
	}

    /**
     * @return boolean
     */
    public function hasOAuth2Client()
    {
        return (bool) $this->oauth2Client;
    }

    /**
     * If we want to use domain events, add this to an abstract class
     */

    // private array $domainEvents = [];

	// public function pullDomainEvents(): array
	// {
	// 	$domainEvents = $this->domainEvents;
	// 	$this->domainEvents = [];

	// 	return $domainEvents;
	// }

	// protected function record(DomainEvent $domainEvent): void
	// {
	// 	$this->domainEvents[] = $domainEvent;
	// }
}
