<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Ring\Exception\ConnectException;
use PrestaShop\Module\PsAccounts\Api\EventBusProxyClient;
use PrestaShop\Module\PsAccounts\Exception\EnvVarException;

class ProxyService
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
     *
     * @throws EnvVarException
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
        } catch (ConnectException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return $response;
    }

    /**
     * @param string $jobId
     * @param array $data
     *
     * @return array
     *
     * @throws EnvVarException
     */
    public function delete($jobId, $data)
    {
        try {
            $compressedData = $this->compressionService->gzipCompressData($data);
        } catch (Exception $exception) {
            return ['error' => $exception->getMessage()];
        }

        try {
            $response = $this->eventBusProxyClient->delete($jobId, $compressedData);
        } catch (ClientException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return $response;
    }
}
