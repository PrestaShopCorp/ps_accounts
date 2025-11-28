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
        $this->assertContains($cloudFrontendUrl, $result[0]['html']);
        $this->assertContains($shopUrl->getFrontendUrl(), $result[0]['html']);
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

