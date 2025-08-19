<?php

namespace PrestaShop\Module\PsAccounts\Http\Controller;

trait GetHeader
{
    /**
     * @param string $header
     *
     * @return string|null
     */
    protected function getRequestHeader($header)
    {
        $headerValue = null;

        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));

        if (array_key_exists($headerKey, $_SERVER)) {
            $headerValue = $_SERVER[$headerKey];
        }

        if (null === $headerValue) {
            $headerValue = $this->getApacheHeader($header);
        }

        return $headerValue;
    }

    /**
     * @param string $header
     *
     * @return string|null
     */
    protected function getApacheHeader($header)
    {
        if (function_exists('apache_request_headers')) {
            $headers = getallheaders();
            //$header = preg_replace('/PrestaShop/', 'Prestashop', $header);
            if (array_key_exists($header, $headers)) {
                return $headers[$header];
            }
        }

        return null;
    }
}
