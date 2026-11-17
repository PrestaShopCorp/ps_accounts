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

namespace PrestaShop\Module\PsAccounts\Tests\Unit;

use PrestaShop\Module\PsAccounts\Tests\TestCase;

/**
 * Relative date arithmetic on a local time is wall-clock arithmetic: across a
 * DST fall-back, '+60 seconds' can add 3660 s (ACC-3716). Expiry times must be
 * computed on Unix timestamps.
 */
class RelativeDateArithmeticTest extends TestCase
{
    /**
     * Known occurrences, each tracked by a ticket.
     *
     * @var string[]
     */
    private $allowed = [
        // ACC-3718: restricted area, value not read in the module
        'Service/OAuth2/Resource/AccessToken.php',
    ];

    /**
     * @test
     */
    public function itShouldNotUseRelativeDateArithmeticInSources()
    {
        $pattern = '/->modify\(|\bdate_modify\(|\bDateInterval\b|'
            . '(new\s+\\\\?DateTime(Immutable)?|\bdate_create(_immutable)?|\bstrtotime)\(\s*[\'"][+-]/';

        $srcDir = realpath(__DIR__ . '/../../../src');
        $this->assertNotFalse($srcDir);

        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($srcDir) + 1));
            if (in_array($relativePath, $this->allowed, true)) {
                continue;
            }
            foreach (file($file->getPathname()) as $index => $line) {
                if (preg_match($pattern, $line)) {
                    $offenders[] = $relativePath . ':' . ($index + 1) . ': ' . trim($line);
                }
            }
        }

        $this->assertSame([], $offenders, 'Compute dates on Unix timestamps, e.g. new \DateTime(\'@\' . ($now + $seconds))');
    }

    /**
     * @test
     */
    public function itShouldStillMatchTheAllowedOccurrences()
    {
        $pattern = '/(new\s+\\\\?DateTime(Immutable)?)\(\s*[\'"][+-]/';

        foreach ($this->allowed as $relativePath) {
            $this->assertSame(
                1,
                preg_match($pattern, (string) file_get_contents(__DIR__ . '/../../../src/' . $relativePath)),
                $relativePath . ' no longer needs to be allowed'
            );
        }
    }
}
