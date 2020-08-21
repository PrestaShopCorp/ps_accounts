<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Context;
use Module;
use PrestaShop\Module\PsAccounts\Api\Client\SegmentClient;
use PrestaShopBundle\Exception\NotImplementedException;

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
     * @var Module
     */
    private $module;

    public function __construct()
    {
        $link = Context::getContext()->link;
        $this->segmentClient = new SegmentClient($link);
        $this->compressionService = new CompressionService();
        $this->module = Module::getInstanceByName('ps_accounts');
    }

    public function finishExport($syncId)
    {
        return $this->segmentClient->finish($syncId);
    }

    public function upload($syncId, $data)
    {
        $compressedDataFilePath = $this->module->getLocalPath() . '/files/' . time() . '.gz';

        if ($this->compressionService->generateGzipCompressedJsonFile($data, $compressedDataFilePath)) {
            $response = $this->segmentClient->upload($syncId, $compressedDataFilePath);
            unlink($compressedDataFilePath);
            return $response;
        }

        return false;
    }
}
