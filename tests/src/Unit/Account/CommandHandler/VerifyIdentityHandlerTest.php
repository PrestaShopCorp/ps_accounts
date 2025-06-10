<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\CachedShopStatus;
use PrestaShop\Module\PsAccounts\Account\Command\VerifyIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\VerifyIdentityHandler;
use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Http\Client\Curl\Client;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class VerifyIdentityHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @inject
     *
     * @var AccountsService
     */
    protected $accountsService;

    /**
     * @inject
     *
     * @var StatusManager
     */
    public $statusManager;

    /**
     * @inject
     *
     * @var ProofManager
     */
    public $proofManager;

    /**
     * @var Client&MockObject
     */
    public $client;

    /**
     * @var ShopSession&MockObject
     */
    public $shopSession;

    /**
     * @var int
     */
    protected $shopId = 1;

    public function set_up()
    {
        parent::set_up();

        $this->shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;
        $this->client = $this->createMock(Client::class);
        $this->accountsService->setClient($this->client);
        $this->shopSession = $this->createMock(ShopSession::class);
    }

    /**
     * @test
     */
    public function itShouldInvalidateCachedStatusOnSuccess()
    {
        $cloudShopId = $this->faker->uuid;

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => false,
            ])
        ]))->toArray()));

        $this->client->method('post')
            ->willReturnCallback(function ($route) use ($cloudShopId) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/verify$/', $route)) {
                    return $this->createResponse([
                        "success" => true,
                    ], 200, true);
                }
                return $this->createResponse([], 500, true);
            });

        $this->getHandler()->handle(new VerifyIdentityCommand(1));

        $this->assertTrue($this->statusManager->cacheInvalidated());
    }

    /**
     * @test
     */
    public function itShouldNotInvalidateCachedStatusOnHttpError()
    {
        $cloudShopId = $this->faker->uuid;

        $this->configurationRepository->updateCachedShopStatus(json_encode((new CachedShopStatus([
            'isValid' => true,
            'shopStatus' => new ShopStatus([
                'cloudShopId' => $cloudShopId,
                'isVerified' => false,
            ])
        ]))->toArray()));

        $this->client->method('post')
            ->willReturnCallback(function ($route) use ($cloudShopId) {
                if (preg_match('/v1\/shop-identities\/' . $cloudShopId . '\/verify$/', $route)) {
                    return $this->createResponse([
                       "error" => 'store-identity/proof-invalid',
                       "message" => 'Proof is invalid',
                    ], 400, true);
                }
                return $this->createResponse([], 400, true);
            });

        try {
            $this->getHandler()->handle(new VerifyIdentityCommand(1));
        } catch (AccountsException $e) {
        }

        $this->assertFalse($this->statusManager->cacheInvalidated());
    }

    /**
     * @return VerifyIdentityHandler
     */
    private function getHandler()
    {
        return new VerifyIdentityHandler(
            $this->accountsService,
            $this->shopProvider,
            $this->statusManager,
            $this->shopSession,
            $this->proofManager
        );
    }
}
