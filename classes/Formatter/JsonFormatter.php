<?php

namespace PrestaShop\Module\PsAccounts\Formatter;

class JsonFormatter
{
    /**
     * @param array $data
     *
     * @return string
     */
    public function formatNewlineJsonString($data)
    {
        $json = '';

        array_map(function ($dataItem) use (&$json) {
            $json .= json_encode($dataItem) . "\r\n";
        }, $data);

        return $json;
    }
}
