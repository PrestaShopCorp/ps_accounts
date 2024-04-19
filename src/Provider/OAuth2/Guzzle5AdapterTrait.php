<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Provider\OAuth2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Message\RequestInterface as GuzzleRequest;
use GuzzleHttp\Message\ResponseInterface as GuzzleResponse;
use GuzzleHttp\Psr7\Response;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\Guzzle5OptionsMapper;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * /!\ Dirty Adapter /!\
 *
 * Adapter for Guzzle at runtime in PrestaShop modules "mixed up" context.
 *
 * On composer, we require explicitly (or implicitly) Guzzle6
 * We can then require league/oauth2-client and get necessary Psr/Response, Psr/Request interfaces
 * at construction time (runtime) we check between Guzzle5 or Guzzle6+
 * IF we have Guzzle6+ we juste call parent method
 * OTHERWISE we call trait adapter method to return Request/Response
 */
trait Guzzle5AdapterTrait
{
    /**
     * @param array $options
     *
     * @return Client|null
     */
    public function buildHttpClient($options)
    {
        /** @var $this PrestaShop */
        /* @phpstan-ignore-next-line */
        $client_options = $this->getAllowedClientOptions($options);

        if (!$this->isGuzzle5()) {
            return null;
        }

        return new Client(
            (new Guzzle5OptionsMapper())->fromGuzzle7Options(
                /* @phpstan-ignore-next-line */
                array_intersect_key($options, array_flip($client_options))
            )
        );
    }

    /**
     * Sends a request instance and returns a response instance.
     *
     * WARNING: This method does not attempt to catch exceptions caused by HTTP
     * errors! It is recommended to wrap this method in a try/catch block.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws GuzzleException
     */
    public function getResponse(RequestInterface $request)
    {
        if (!$this->isGuzzle5()) {
            return parent::getResponse($request);
        }

        /** @var $this PrestaShop */
        /* @phpstan-ignore-next-line */
        $guzzle5Response = $this->getHttpClient()->send($this->createGuzzleRequest($request));

        /* @phpstan-ignore-next-line */
        return $this->createPsrResponse($guzzle5Response);
    }

    /**
     * @return bool
     */
    public function isGuzzle5()
    {
        return ((int) GuzzleClientFactory::getGuzzleMajorVersionNumber()) < 6;
    }

    /**
     * @return int|null
     */
    public function getGuzzleMajorVersionNumber()
    {
        return GuzzleClientFactory::getGuzzleMajorVersionNumber();
    }

    /**
     * Converts a PSR request into a Guzzle request.
     *
     * @param RequestInterface $request
     *
     * @phpstan-ignore-next-line
     *
     * @return GuzzleRequest
     */
    private function createGuzzleRequest(RequestInterface $request)
    {
        $options = [
            'exceptions' => false,
            'allow_redirects' => false,
        ];

        $options['version'] = $request->getProtocolVersion();
        $options['headers'] = $request->getHeaders();
        $body = (string) $request->getBody();
        $options['body'] = '' === $body ? null : $body;

        /* @phpstan-ignore-next-line */
        return $this->getHttpClient()->createRequest(
            $request->getMethod(),
            (string) $request->getUri(),
            $options
        );
    }

    /**
     * Converts a Guzzle response into a PSR response.
     *
     * @phpstan-ignore-next-line
     *
     * @param GuzzleResponse $response
     *
     * @phpstan-ignore-next-line
     *
     * @return ResponseInterface
     */
    private function createPsrResponse(GuzzleResponse $response)
    {
        /* @phpstan-ignore-next-line */
        $body = $response->getBody();

        return new Response(
            /* @phpstan-ignore-next-line */
            $response->getStatusCode(),
            /* @phpstan-ignore-next-line */
            $response->getHeaders(),
            /* @phpstan-ignore-next-line */
            isset($body) ? $body->detach() : null,
            /* @phpstan-ignore-next-line */
            $response->getProtocolVersion()
        );
    }
}
