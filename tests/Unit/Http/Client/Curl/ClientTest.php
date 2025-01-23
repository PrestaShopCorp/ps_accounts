<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Http\Client\Curl;

use PrestaShop\Module\PsAccounts\Http\Client\Factory;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ClientTest extends TestCase
{
    public function it_should_get_default_response_on_timeout()
    {
        $httpClient = (new Factory())->create([
            'name' => static::class,
            'baseUri' => $this->faker->url,
            'headers' => [],
            'timeout' => 1,
            'sslCheck' => false,
            'objectResponse' => true,
        ]);

        // FIXME: LegacyResponseClient
        // FIXME: test curl options over PHP Versions
        // FIXME: test with accounts-api timeout
        // FIXME: test with oauth2 timeout
        // FIXME: test with wrong url (404)
        // TODO
        $httpClient->get('/toto', [

        ]);
    }
}
