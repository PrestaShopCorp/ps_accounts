<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Hook;

use PrestaShop\Module\PsAccounts\Account\Command\VerifyIdentityCommand;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Hook\ActionAdminLoginControllerSetMedia;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ActionAdminLoginControllerSetMediaTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    public $shopProvider;

    /**
     * @inject
     *
     * @var AccountsService
     */
    public $accountsService;

    /**
     * @test
     */
    public function itShouldNotCallVerifyIdentityWhenShopNotVerified()
    {
        $shopUrl = $this->shopProvider->getUrl($this->shopProvider->getShopContext()->getContext()->shop->id);

        // Mock statusManager to return unverified status
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'isVerified' => false,
            'frontendUrl' => $shopUrl->getFrontendUrl(),
            'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
        ]));

        // Mock commandBus to verify it's not called
        $commandBusMock = $this->createMock(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
        $commandBusMock->expects($this->never())->method('handle');

        $hook = $this->createHookWithMocks($statusManagerMock, $commandBusMock);
        $method = $this->getProtectedMethod($hook, 'checkAndUpdateUrlIfNeeded');

        $method->invoke($hook);
    }

    /**
     * @test
     */
    public function itShouldNotCallVerifyIdentityWhenBackOfficeUrlNotChanged()
    {
        $shopUrl = $this->shopProvider->getUrl($this->shopProvider->getShopContext()->getContext()->shop->id);

        // Mock statusManager to return verified status with same URLs
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'isVerified' => true,
            'frontendUrl' => $shopUrl->getFrontendUrl(),
            'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
        ]));

        // Mock commandBus to verify it's not called
        $commandBusMock = $this->createMock(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
        $commandBusMock->expects($this->never())->method('handle');

        $hook = $this->createHookWithMocks($statusManagerMock, $commandBusMock);
        $method = $this->getProtectedMethod($hook, 'checkAndUpdateUrlIfNeeded');

        $method->invoke($hook);
    }

    /**
     * @test
     */
    public function itShouldNotCallVerifyIdentityWhenBothUrlsChanged()
    {
        $shopUrl = $this->shopProvider->getUrl($this->shopProvider->getShopContext()->getContext()->shop->id);

        // Mock statusManager to return verified status with different URLs (both changed)
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'isVerified' => true,
            'frontendUrl' => 'https://different-frontend.com',
            'backOfficeUrl' => 'https://different-backoffice.com',
        ]));

        // Mock commandBus to verify it's not called (because both URLs changed)
        $commandBusMock = $this->createMock(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
        $commandBusMock->expects($this->never())->method('handle');

        $hook = $this->createHookWithMocks($statusManagerMock, $commandBusMock);
        $method = $this->getProtectedMethod($hook, 'checkAndUpdateUrlIfNeeded');

        $method->invoke($hook);
    }

    /**
     * @test
     */
    public function itShouldCallVerifyIdentityWhenOnlyBackOfficeUrlChanged()
    {
        $shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;
        $shopUrl = $this->shopProvider->getUrl($shopId);

        // Get URLs and normalize them exactly as onlyBackOfficeUrlChanged does
        $localFrontendUrl = rtrim($shopUrl->getFrontendUrl(), '/');
        $localBackOfficeUrl = rtrim($shopUrl->getBackOfficeUrl(), '/');

        // Mock statusManager to return verified status with only backOfficeUrl changed
        // frontendUrl must match exactly after rtrim
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'isVerified' => true,
            'frontendUrl' => $localFrontendUrl, // Same as local (normalized with rtrim)
            'backOfficeUrl' => 'https://different-backoffice.com', // Different from local
        ]));

        // Ensure context shop ID matches - this is critical for Shop::getContextShopID()
        $context = $this->module->getContext();
        if ($context && isset($context->shop)) {
            $context->shop->id = $shopId;
        }

        // Also ensure Shop context is set to the correct shop
        if (class_exists('\Shop')) {
            \Shop::setContext(\Shop::CONTEXT_SHOP, $shopId);
        }

        // Mock commandBus to verify it's called with VerifyIdentityCommand
        $commandBusMock = $this->createMock(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
        $commandBusMock->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($command) use ($shopId) {
                return $command instanceof VerifyIdentityCommand
                    && $command->shopId === $shopId
                    && $command->force === true
                    && $command->origin === \PrestaShop\Module\PsAccounts\Service\Accounts\AccountsService::ORIGIN_INSTALL;
            }));

        $hook = $this->createHookWithMocks($statusManagerMock, $commandBusMock);
        $method = $this->getProtectedMethod($hook, 'checkAndUpdateUrlIfNeeded');

        $method->invoke($hook);
    }

    /**
     * @param StatusManager $statusManagerMock
     * @param \PrestaShop\Module\PsAccounts\Cqrs\CommandBus $commandBusMock
     *
     * @return ActionAdminLoginControllerSetMedia
     */
    private function createHookWithMocks($statusManagerMock, $commandBusMock)
    {
        // Create a module mock that returns our mocks
        $moduleMock = $this->createMock(\Ps_accounts::class);
        $moduleMock->method('getService')
            ->willReturnCallback(function ($class) use ($statusManagerMock, $commandBusMock) {
                if ($class === StatusManager::class) {
                    return $statusManagerMock;
                }
                if ($class === \PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class) {
                    return $commandBusMock;
                }
                if ($class === \PrestaShop\Module\PsAccounts\Provider\ShopProvider::class) {
                    return $this->shopProvider;
                }
                // Fallback to real service
                return $this->module->getService($class);
            });
        $moduleMock->method('getLogger')->willReturn($this->module->getLogger());

        return new ActionAdminLoginControllerSetMedia($moduleMock);
    }

    /**
     * @param object $object
     * @param string $methodName
     *
     * @return \ReflectionMethod
     */
    private function getProtectedMethod($object, $methodName)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

}

