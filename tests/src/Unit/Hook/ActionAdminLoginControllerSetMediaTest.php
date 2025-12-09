<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Hook;

use PrestaShop\Module\PsAccounts\Account\Command\UpdateBackOfficeUrlsCommand;
use PrestaShop\Module\PsAccounts\Hook\ActionAdminLoginControllerSetMedia;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ActionAdminLoginControllerSetMediaTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldCallUpdateBackOfficeUrlsCommand()
    {
        // Mock commandBus to verify it's called with UpdateBackOfficeUrlsCommand
        $commandBusMock = $this->createMock(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
        $commandBusMock->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(UpdateBackOfficeUrlsCommand::class));

        $hook = $this->createHookWithMocks($commandBusMock);
        $method = $this->getProtectedMethod($hook, 'checkAndUpdateUrlIfNeeded');

        $method->invoke($hook);
    }

    /**
     * @test
     */
    public function itShouldNotThrowExceptionWhenCommandFails()
    {
        // Mock commandBus to throw an exception
        $commandBusMock = $this->createMock(\PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class);
        $commandBusMock->expects($this->once())
            ->method('handle')
            ->willThrowException(new \Exception('Test exception'));

        $hook = $this->createHookWithMocks($commandBusMock);
        $method = $this->getProtectedMethod($hook, 'checkAndUpdateUrlIfNeeded');

        // Should not throw exception - it should be caught and logged
        $method->invoke($hook);
    }

    /**
     * @param \PrestaShop\Module\PsAccounts\Cqrs\CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBusMock
     *
     * @return ActionAdminLoginControllerSetMedia
     */
    private function createHookWithMocks($commandBusMock)
    {
        // Create a module mock that returns our mocks
        $moduleMock = $this->createMock(\Ps_accounts::class);
        $moduleMock->method('getService')
            ->willReturnCallback(function ($class) use ($commandBusMock) {
                if ($class === \PrestaShop\Module\PsAccounts\Cqrs\CommandBus::class) {
                    return $commandBusMock;
                }
                // Fallback to real service
                return $this->module->getService($class);
            });
        $moduleMock->method('getLogger')->willReturn($this->module->getLogger());

        /** @var \Ps_accounts $moduleMock */
        $hook = new ActionAdminLoginControllerSetMedia($moduleMock);

        // Inject commandBus using reflection to override the one set in constructor
        $reflection = new \ReflectionClass($hook);
        $commandBusProperty = $reflection->getProperty('commandBus');
        $commandBusProperty->setAccessible(true);
        $commandBusProperty->setValue($hook, $commandBusMock);

        return $hook;
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

