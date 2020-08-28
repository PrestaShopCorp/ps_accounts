<?php

namespace PrestaShop\Module\PsAccounts\Service;

class CompressionService
{
    /**
     * Compresses data with gzip
     *
     * @param array $data
     *
     * @return string|false
     */
    public function gzipCompressData($data)
    {
        $dataJson = json_encode($data);

        if (!$dataJson || !extension_loaded('zlib')) {
            return false;
        }

        return gzencode($dataJson);
    }
}
