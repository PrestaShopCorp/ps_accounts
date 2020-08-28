<?php

namespace PrestaShop\Module\PsAccounts\Service;

class CompressionService
{
    /**
     * Creates a gzip compressed file with passed data
     *
     * @param array $data
     * @param string $filePath
     *
     * @return bool
     */
    public function generateGzipCompressedJsonFile($data, $filePath)
    {
        if (!extension_loaded('zlib')) {
            return false;
        }

        $data = json_encode($data);

        $fileHandle = fopen($filePath, 'w');
        fwrite($fileHandle, gzencode($data));
        fclose($fileHandle);

        return true;
    }

    /**
     * Compresses data with gzip
     *
     * @param array $data
     *
     * @return string
     */
    public function gzipCompressData($data)
    {
        if (!extension_loaded('zlib')) {
            return false;
        }

        return gzencode(json_encode($data));
    }
}
