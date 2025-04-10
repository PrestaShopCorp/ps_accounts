<?php

namespace PrestaShop\Module\PsAccounts\Tests;

if (version_compare(phpversion(), '7.1', '>=')) {
    class TestCase extends TestCase71
    {
    }
} else {
    class TestCase extends TestCase56
    {
    }
}

