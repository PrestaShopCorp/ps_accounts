<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Exception;
use PrestaShop\Module\PsAccounts\Formatter\JsonFormatter;

class CompressionService
{
    /**
     * @var JsonFormatter
     */
    private $jsonFormatter;

    public function __construct(JsonFormatter $jsonFormatter)
    {
        $this->jsonFormatter = $jsonFormatter;
    }

    /**
     * Compresses data with gzip
     *
     * @param array $data
     *
     * @return string
     *
     * @throws Exception
     */
    public function gzipCompressData($data)
    {
        if (!extension_loaded('zlib')) {
            throw new Exception('Zlib extension for PHP is not enabled');
        }

        $dataJson = $this->jsonFormatter->formatNewlineJsonString($data);

        if (!$encodedData = gzencode($dataJson)) {
            throw new Exception('Failed encoding data to GZIP');
        }

        return $encodedData;
    }
}
