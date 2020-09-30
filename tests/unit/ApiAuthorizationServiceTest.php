<?php

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\PsAccounts\Api\Client\EventBusSyncClient;
use PrestaShop\Module\PsAccounts\Repository\AccountsSyncRepository;
use PrestaShop\Module\PsAccounts\Service\ApiAuthorizationService;

class ApiAuthorizationServiceTest extends TestCase
{
    /**
     * @var AccountsSyncRepository
     */
    private $accountsSyncRepository;
    /**
     * @var ApiAuthorizationService
     */
    private $apiAuthorizationService;
    /**
     * @var EventBusSyncClient
     */
    private $eventBusSyncClient;

    public function setUp()
    {
        parent::setUp();

        $this->accountsSyncRepository = $this->createMock(AccountsSyncRepository::class);
        $this->eventBusSyncClient = $this->createMock(EventBusSyncClient::class);
        $this->apiAuthorizationService = new ApiAuthorizationService(
            $this->accountsSyncRepository,
            $this->eventBusSyncClient
        );
    }

    public function testAuthorizeCallSucceeds()
    {
        $jobId = '12345';

        $this->accountsSyncRepository
            ->expects($this->at(0))
            ->method('findSyncStateByJobId')
            ->with($jobId)
            ->willReturn(['job_id' => '12345']);
        $this->eventBusSyncClient->method('validateJobId')->willReturn(true);

        $this->assertTrue($this->apiAuthorizationService->authorizeCall($jobId));

        $this->accountsSyncRepository
            ->expects($this->at(0))
            ->method('findSyncStateByJobId')
            ->with($jobId)
            ->willReturn(false);
        $this->eventBusSyncClient->method('validateJobId')->willReturn(true);

        $this->accountsSyncRepository
            ->expects($this->atLeastOnce())
            ->method('insertSync')
            ->willReturn(true);

        $this->assertTrue($this->apiAuthorizationService->authorizeCall($jobId));
    }

    public function testAuthorizeCallFails()
    {
        $jobId = '12345';

        $this->accountsSyncRepository
            ->expects($this->at(0))
            ->method('findSyncStateByJobId')
            ->with($jobId)
            ->willReturn(false);

        $this->accountsSyncRepository
            ->expects($this->atLeastOnce())
            ->method('insertSync')
            ->willReturn(false);

        $this->eventBusSyncClient->method('validateJobId')->willReturn(true);

        $this->assertFalse($this->apiAuthorizationService->authorizeCall($jobId));
    }
}
