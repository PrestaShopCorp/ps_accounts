<?php

namespace PrestaShop\Module\PsAccounts\Identity\Domain;

class Identity
{
    /**
     * @var string
     */
	private $cloudShopId;

    // TODO: add local shop id ?

    /**
     * @var Oauth2Client
     */
	private $oauth2Client;

	public function __construct($cloudShopId, Oauth2Client $oauth2Client = null) {
		$this->cloudShopId = $cloudShopId;
		$this->oauth2Client = $oauth2Client;
	}

	public function create($cloudShopId, Oauth2Client $oauth2Client)
	{
		$this->cloudShopId = $cloudShopId;
		$this->oauth2Client = $oauth2Client;

		// $this->record(new IdentityCreated($this->id, $this->oauth2Client));
	}

	public function verify()
	{
		// $this->record(new IdentityVerified($this->id));
	}

	public function cloudShopId(): string
	{
		return $this->cloudShopId;
	}

	public function oauth2Client(): OAuth2Client
	{
		return $this->oauth2Client;
	}

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
