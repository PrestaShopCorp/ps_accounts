<?php

namespace PrestaShop\Module\PsAccounts\Formatter;

class ArrayFormatter
{
    public function formatArray(array $data, $separator = ';')
    {
        return implode($separator, $data);
    }

    public function formatValueArray(array $data, $separator = ';')
    {
        return implode($separator, array_map(function ($dataItem) {
            return $dataItem['value'];
        }, $data));
    }
}
