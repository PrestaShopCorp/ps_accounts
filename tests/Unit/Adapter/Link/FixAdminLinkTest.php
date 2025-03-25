<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Adapter\Link;

use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class FixAdminLinkTest extends TestCase
{
    /**
     * @inject
     *
     * @var Link
     */
    protected $link;

    // TODO: OAuth2ClientTest

    public function data()
    {
        return [
            [
                'https://',
                'www.donut.com/' . _PS_ADMIN_DIR_ . '/index.php',
                'foobar.com',
                'foobar-ssl.com',
                'subdir',
                'index.php',
                '',
            ],
            [
                'http://',
                'www.donut.com/' . _PS_ADMIN_DIR_ . '/index.php',
                'foobar.com',
                'foobar-ssl.com',
                'subdir',
                'index.php',
                '',
            ],
            [
                'https://',
                'www.donut.com/' . _PS_ADMIN_DIR_,
                'foobar.com',
                'foobar-ssl.com',
                '/subdir/',
                '',
                '',
            ],
            [
                'https://',
                'www.donut.com/' . _PS_ADMIN_DIR_,
                'foobar.com',
                'foobar-ssl.com',
                '',
                '',
                '',
            ],
            [
                'https://',
                'www.donut.com/tata/' . _PS_ADMIN_DIR_ . '/',
                'foobar.com',
                'foobar-ssl.com',
                '',
                '',
                '/',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider data
     */
    public function itShouldFixDomainAndPath(
        $scheme,
        $uri,
        $domain,
        $domainSsl,
        $physicalUri,
        $index,
        $endSlash
    ) {
        $uri = $this->link->cleanSlashes($uri);

        $shop = new \Shop(1);
        $shop->domain = $domain;
        $shop->domain_ssl = $domainSsl;
        $shop->physical_uri = $physicalUri;

        $uri = $scheme . $uri;

        Logger::getInstance()->info('AVANT : ' . $uri);

        $uri = $this->link->fixAdminLink($uri, $shop);

        Logger::getInstance()->info('APRÈS : ' . $uri);

        $parsed = parse_url($uri);
        $this->assertEquals(
            $scheme === 'https://' ? $domainSsl : $domain,
            $parsed['host']
        );
        $this->assertEquals(
            $this->link->cleanSlashes(
                '/' . $physicalUri . _PS_ADMIN_DIR_ . ($index ? '/' . $index : '') . $endSlash
            ),
            $parsed['path']
        );
    }
}
