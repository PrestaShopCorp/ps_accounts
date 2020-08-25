<?php

namespace PrestaShop\Module\PsAccounts\Service;

class CompressionService
{
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
}
