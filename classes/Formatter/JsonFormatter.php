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
        $jsonArray = array_map(function ($dataItem) {
            return json_encode($dataItem, JSON_UNESCAPED_SLASHES);
        }, $data);

        return implode("\r\n", $jsonArray);
    }
}
