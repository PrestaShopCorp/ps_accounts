<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use GuzzleHttp\Exception\ClientException;
use Module;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;
use Ps_accounts;

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
    /**
     * @var Ps_accounts
     */
    private $module;

    public function __construct(Context $context)
    {
        $this->segmentClient = new SegmentClient($context->link);
        $this->compressionService = new CompressionService();
        $this->module = Module::getInstanceByName('ps_accounts');
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
