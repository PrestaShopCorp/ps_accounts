<?php

namespace PrestaShop\Module\PsAccounts\Service;

class CompressionService
{
    public function generateGzipCompressedJsonFile($data, $filePath)
    {
        if (!extension_loaded('gzip')) {
            return false;
        }

        $data = json_encode($data);

        $fileHandle = fopen($filePath, 'w+');
        fwrite($fileHandle, gzencode($data));
        fclose($fileHandle);

        return true;
    }
}
