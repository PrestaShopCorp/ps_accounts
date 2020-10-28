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
     * @param string|int $key
     *
     * @return array
     */
    public function formatValueArray(array $data, $key)
    {
        return array_map(function ($dataItem) use ($key) {
            return $dataItem[$key];
        }, $data);
    }

    /**
     * @param array $data
     * @param string|int $key
     * @param string $separator
     *
     * @return string
     */
    public function formatValueString(array $data, $key, $separator = ';')
    {
        return implode($separator, $this->formatValueArray($data, $key));
    }
}
