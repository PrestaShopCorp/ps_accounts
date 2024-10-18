<?php

namespace PrestaShop\Module\PsAccounts\Identity\Infrastructure;

use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Identity\Domain\Identity;
use PrestaShop\Module\PsAccounts\Identity\Domain\IdentityManager;
use PrestaShop\Module\PsAccounts\Identity\Domain\OAuth2Client;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;

class ConfigurationIdentityManager implements IdentityManager
{
    /**
     * @var ShopContext
     */
    protected $shopContext;

    /**
     * @var ConfigurationRepository
     */
	private $configurationRepository;

	public function __construct(
        ShopContext $shopContext,
        ConfigurationRepository $configurationRepository
    ) {
        $this->shopContext = $shopContext;
        $this->configurationRepository = $configurationRepository;
    }

    public function get($shopId)
    {
        return $this->shopContext->execInShopContext($shopId, function() use ($shopId) {
            $oauth2Client = new OAuth2Client(
                $this->configurationRepository->getOauth2ClientId(),
                $this->configurationRepository->getOauth2ClientSecret()
            );

            return new Identity($shopId, $this->configurationRepository->getShopUuid(), $oauth2Client);
        });
    }

	public function save(Identity $identity)
	{
        return $this->shopContext->execInShopContext($identity->shopId(), function() use ($identity) {
            $oauth2ClientId = $identity->hasOAuth2Client() ? $identity->oauth2Client()->id() : null;
            $oauth2ClientSecret = $identity->hasOAuth2Client() ? $identity->oauth2Client()->secret() : null;

            $this->configurationRepository->updateShopUuid($identity->cloudShopId());
            $this->configurationRepository->updateOauth2ClientId($oauth2ClientId);
            $this->configurationRepository->updateOauth2ClientSecret($oauth2ClientSecret);
        });
	}
}
