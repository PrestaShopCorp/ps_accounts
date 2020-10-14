<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use PrestaShop\Module\PsAccounts\Api\EventBusProxyClient;

class SegmentService
{
    /**
     * @var EventBusProxyClient
     */
    private $eventBusProxyClient;
    /**
     * @var CompressionService
     */
    private $compressionService;

    public function __construct(EventBusProxyClient $eventBusProxyClient, CompressionService $compressionService)
    {
        $this->eventBusProxyClient = $eventBusProxyClient;
        $this->compressionService = $compressionService;
    }

    /**
     * @param string $jobId
     * @param array $data
     *
     * @return array
     */
    public function upload($jobId, $data)
    {
        try {
            $compressedData = $this->compressionService->gzipCompressData($data);
        } catch (Exception $exception) {
            return ['error' => $exception->getMessage()];
        }

        try {
            $response = $this->eventBusProxyClient->upload($jobId, $compressedData);
        } catch (ClientException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return $response;
    }
}
