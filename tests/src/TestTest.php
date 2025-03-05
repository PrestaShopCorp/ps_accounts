<?php

namespace src;

use PHPUnit\Framework\TestCase;

class TestTest extends TestCase
{
    /**
     * @test
     */
    public function fooBar()
    {
        $this->assertEquals(true, true);
    }
}
