<?php

namespace PrestaShop\Module\PsAccounts\Formatter;

class ArrayFormatter
{
    /**
     * @param array $data
     * @param string $separator
     *
     * @return string
     */
    public function formatArray(array $data, $separator = ';')
    {
        return implode($separator, $data);
    }

    /**
     * @param array $data
     * @param string $separator
     *
     * @return string
     */
    public function formatValueArray(array $data, $separator = ';')
    {
        return implode($separator, array_map(function ($dataItem) {
            return $dataItem['value'];
        }, $data));
    }
}
