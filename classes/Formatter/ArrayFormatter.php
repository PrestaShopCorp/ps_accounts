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
    public function arrayToString(array $data, $separator = ';')
    {
        return implode($separator, $data);
    }

    /**
     * @param array $data
     * @param string|int $key
     * @param bool $unique
     *
     * @return array
     */
    public function formatValueArray(array $data, $key, $unique = false)
    {
        $result = array_map(function ($dataItem) use ($key) {
            return $dataItem[$key];
        }, $data);

        if ($unique) {
            return $this->unique($result);
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function unique(array $data)
    {
        return array_unique($data);
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
