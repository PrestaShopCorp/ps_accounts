<?php

namespace PrestaShop\Module\PsAccounts\Service;

use Exception;

class CompressionService
{
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
        } elseif (!$dataJson = json_encode($data)) {
            throw new Exception('Failed encoding data to JSON');
        } elseif (!$encodedData = gzencode($dataJson)) {
            throw new Exception('Failed encoding data to GZIP');
        }

        return $encodedData;
    }
}
