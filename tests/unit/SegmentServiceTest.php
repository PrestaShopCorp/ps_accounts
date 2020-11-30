<?php

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use PrestaShop\Module\PsAccounts\Api\EventBusProxyClient;
use PrestaShop\Module\PsAccounts\Service\CompressionService;
use PrestaShop\Module\PsAccounts\Service\ProxyService;

class ProxyServiceTest extends TestCase
{
    /**
     * @var ProxyService
     */
    private $segmentService;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var EventBusProxyClient
     */
    private $eventBusProxyClient;
    /**
     * @var CompressionService
     */
    private $compressionService;

    public function setUp()
    {
        parent::setUp();
        $this->context = Context::getContext();
        $this->eventBusProxyClient = $this->createMock(EventBusProxyClient::class);
        $this->compressionService = $this->createMock(CompressionService::class);
        $this->segmentService = new ProxyService($this->eventBusProxyClient, $this->compressionService);
    }

    public function testValidUpload()
    {
        $data = ['important_server_data' => ':)'];
        $syncId = '12345';
        $compressedData = gzencode(json_encode($data));

        $this->compressionService->method('gzipCompressData')->willReturn($compressedData);
        $this->eventBusProxyClient->method('upload')->willReturn([
            'status' => 'success',
            'httpCode' => 201,
            'body' => 'success',
        ]);

        $this->assertTrue(is_array($this->segmentService->upload($syncId, $compressedData)));
        $this->assertArrayHasKey('httpCode', $this->segmentService->upload($syncId, $compressedData));
        $this->assertEquals(201, $this->segmentService->upload($syncId, $compressedData)['httpCode']);
    }

    public function testInvalidUpload()
    {
        $data = ['important_server_data' => ':)'];
        $syncId = '12345';

        $this->compressionService->method('gzipCompressData')->willReturn(false);
        $this->assertFalse($this->segmentService->upload($syncId, $data));

        $this->compressionService->method('gzipCompressData')->willReturn('compressed');
        $this->assertFalse($this->segmentService->upload($syncId, $data));

        $clientException = $this->createMock(ClientException::class);
        $this->eventBusProxyClient->method('upload')->willThrowException($clientException);
        $this->assertFalse($this->segmentService->upload($syncId, $data));
    }
}
