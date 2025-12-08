<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Controllers;

use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class AdminAjaxPsAccountsControllerTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    public $shopProvider;

    /**
     * @test
     */
    public function itShouldReturnEmptyArrayWhenIdentityNotCreated()
    {
        // Mock statusManager to return false for identityCreated
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('identityCreated')->willReturn(false);

        $controller = $this->createControllerMockWithMocks($statusManagerMock);
        $method = $this->getProtectedMethod($controller, 'getNotificationsUrlMismatch');

        $result = $method->invoke($controller);

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function itShouldReturnEmptyArrayWhenFrontendUrlNotChanged()
    {
        $shopUrl = $this->shopProvider->getUrl($this->shopProvider->getShopContext()->getContext()->shop->id);

        // Mock statusManager
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('identityCreated')->willReturn(true);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'frontendUrl' => $shopUrl->getFrontendUrl(),
            'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
        ]));

        $controller = $this->createControllerMockWithMocks($statusManagerMock);
        $method = $this->getProtectedMethod($controller, 'getNotificationsUrlMismatch');

        $result = $method->invoke($controller);

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function itShouldReturnNotificationWhenFrontendUrlChanged()
    {
        $shopUrl = $this->shopProvider->getUrl($this->shopProvider->getShopContext()->getContext()->shop->id);
        $cloudFrontendUrl = 'https://different-example.com';

        // Mock statusManager
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('identityCreated')->willReturn(true);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'frontendUrl' => $cloudFrontendUrl,
            'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
        ]));

        $controller = $this->createControllerMockWithMocks($statusManagerMock);
        $method = $this->getProtectedMethod($controller, 'getNotificationsUrlMismatch');

        $result = $method->invoke($controller);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('html', $result[0]);
        $this->assertStringContainsString($cloudFrontendUrl, $result[0]['html']);
        $this->assertStringContainsString($shopUrl->getFrontendUrl(), $result[0]['html']);
    }

    /**
     * @test
     */
    public function itShouldReturnEmptyArrayWhenCloudFrontendUrlIsEmpty()
    {
        $shopUrl = $this->shopProvider->getUrl($this->shopProvider->getShopContext()->getContext()->shop->id);

        // Mock statusManager with empty cloudFrontendUrl
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('identityCreated')->willReturn(true);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'frontendUrl' => '',
            'backOfficeUrl' => $shopUrl->getBackOfficeUrl(),
        ]));

        $controller = $this->createControllerMockWithMocks($statusManagerMock);
        $method = $this->getProtectedMethod($controller, 'getNotificationsUrlMismatch');

        $result = $method->invoke($controller);

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function itShouldReturnEmptyArrayWhenLocalFrontendUrlIsEmpty()
    {
        // Create a mock shopUrl with empty frontendUrl
        $shopUrlMock = $this->createMock(\PrestaShop\Module\PsAccounts\Account\ShopUrl::class);
        $shopUrlMock->method('getFrontendUrl')->willReturn('');
        $shopUrlMock->method('getBackOfficeUrl')->willReturn('https://admin.example.com');

        // Mock shopProvider to return our mock shopUrl
        $shopProviderMock = $this->createMock(\PrestaShop\Module\PsAccounts\Provider\ShopProvider::class);
        $shopProviderMock->method('getUrl')->willReturn($shopUrlMock);
        $shopProviderMock->method('getShopContext')->willReturn($this->shopProvider->getShopContext());

        // Mock statusManager
        $statusManagerMock = $this->createMock(StatusManager::class);
        $statusManagerMock->method('identityCreated')->willReturn(true);
        $statusManagerMock->method('getStatus')->willReturn(new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]));

        // Create module mock
        $moduleMock = $this->createMock(\Ps_accounts::class);
        $moduleMock->method('getService')
            ->willReturnCallback(function ($class) use ($statusManagerMock, $shopProviderMock) {
                if ($class === StatusManager::class) {
                    return $statusManagerMock;
                }
                if ($class === \PrestaShop\Module\PsAccounts\Provider\ShopProvider::class) {
                    return $shopProviderMock;
                }
                if ($class === \PrestaShop\Module\PsAccounts\Adapter\Link::class) {
                    return $this->module->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);
                }
                return $this->module->getService($class);
            });
        $moduleMock->method('l')->willReturnCallback(function ($string, $class) {
            return $this->module->l($string, $class);
        });

        $controller = new \AdminAjaxPsAccountsController();
        $controller->module = $moduleMock;

        // Set context using reflection
        $reflection = new \ReflectionClass($controller);
        if ($reflection->hasProperty('context')) {
            $contextProperty = $reflection->getProperty('context');
            $contextProperty->setAccessible(true);
            $contextProperty->setValue($controller, $this->module->getContext());
        }
        if ($reflection->hasProperty('translationClass')) {
            $translationProperty = $reflection->getProperty('translationClass');
            $translationProperty->setAccessible(true);
            $translationProperty->setValue($controller, 'AdminAjaxPsAccountsController');
        }

        $method = $this->getProtectedMethod($controller, 'getNotificationsUrlMismatch');
        $result = $method->invoke($controller);

        $this->assertEquals([], $result);
    }

    /**
     * @param StatusManager $statusManagerMock
     *
     * @return \AdminAjaxPsAccountsController
     */
    private function createControllerMockWithMocks($statusManagerMock)
    {
        // Create a module mock that returns our mocks
        $moduleMock = $this->createMock(\Ps_accounts::class);
        $moduleMock->method('getService')
            ->willReturnCallback(function ($class) use ($statusManagerMock) {
                if ($class === StatusManager::class) {
                    return $statusManagerMock;
                }
                if ($class === ShopProvider::class) {
                    return $this->shopProvider;
                }
                if ($class === \PrestaShop\Module\PsAccounts\Adapter\Link::class) {
                    return $this->module->getService(\PrestaShop\Module\PsAccounts\Adapter\Link::class);
                }
                // Fallback to real service
                return $this->module->getService($class);
            });
        $moduleMock->method('l')->willReturnCallback(function ($string, $class) {
            return $this->module->l($string, $class);
        });

        $controller = new \AdminAjaxPsAccountsController();
        $controller->module = $moduleMock;

        // Set context and translationClass using reflection
        $reflection = new \ReflectionClass($controller);

        // Set context
        if ($reflection->hasProperty('context')) {
            $contextProperty = $reflection->getProperty('context');
            $contextProperty->setAccessible(true);
            $contextProperty->setValue($controller, $this->module->getContext());
        }

        // Set translationClass
        if ($reflection->hasProperty('translationClass')) {
            $translationProperty = $reflection->getProperty('translationClass');
            $translationProperty->setAccessible(true);
            $translationProperty->setValue($controller, 'AdminAjaxPsAccountsController');
        }

        return $controller;
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

