<?php

namespace PrestaShop\Module\PsAccounts\Service;

use GuzzleHttp\Exception\ClientException;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;

class SegmentService
{
    /**
     * @var SegmentClient
     */
    private $segmentClient;
    /**
     * @var CompressionService
     */
    private $compressionService;

    public function __construct(
        SegmentClient $segmentClient,
        CompressionService $compressionService
    ) {
        $this->segmentClient = $segmentClient;
        $this->compressionService = $compressionService;
    }

    /**
     * @param $syncId
     * @return array
     */
    public function finishExport($syncId)
    {
        return $this->segmentClient->finish($syncId);
    }

    /**
     * @param $syncId
     * @param $data
     * @return array|bool
     */
    public function upload($syncId, $data)
    {
        if (!$compressedData = $this->compressionService->gzipCompressData($data)) {
            return false;
        }

        try {
            $response = $this->segmentClient->upload($syncId, $compressedData);
        } catch (ClientException $exception) {
            return false;
        }

        return $response;
    }
}
