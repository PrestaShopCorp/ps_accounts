<?php

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;
use GuzzleHttp\Exception\ClientException;
use PrestaShop\Module\PsAccounts\Service\CompressionService;
use PrestaShop\Module\PsAccounts\Service\SegmentService;

class SegmentServiceTest extends TestCase
{
    /**
     * @var SegmentService
     */
    private $segmentService;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var SegmentClient
     */
    private $segmentClient;
    /**
     * @var CompressionService
     */
    private $compressionService;

    public function setUp()
    {
        parent::setUp();
        $this->context = Context::getContext();
        $this->segmentClient = $this->createMock(SegmentClient::class);
        $this->compressionService = $this->createMock(CompressionService::class);
        $this->segmentService = new SegmentService($this->segmentClient, $this->compressionService);
    }

    public function testValidUpload()
    {
        $data = ['important_server_data' => ':)'];
        $syncId = '12345';
        $compressedData = gzencode(json_encode($data));

        $this->compressionService->method('gzipCompressData')->willReturn($compressedData);
        $this->segmentClient->method('upload')->willReturn([
            'status' => 'success',
            'httpCode' => 201,
            'body' => "success",
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
        $this->segmentClient->method('upload')->willThrowException($clientException);
        $this->assertFalse($this->segmentService->upload($syncId, $data));
    }
}
