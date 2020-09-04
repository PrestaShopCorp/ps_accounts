<?php

namespace PrestaShop\Module\PsAccounts\Formatter;

use PHPUnit\Framework\TestCase;

class JsonFormatterTest extends TestCase
{
    /**
     * @var JsonFormatter
     */
    private $jsonFormatter;

    protected function setUp()
    {
        parent::setUp();
        $this->jsonFormatter = new JsonFormatter();
    }

    public function testFormatNewlineJsonString()
    {
        $data = [
            ['test' => 'data'],
        ];

        $this->assertTrue(is_string($this->jsonFormatter->formatNewlineJsonString($data)));
    }
}
