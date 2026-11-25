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

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Lint;

use PrestaShop\Module\PsAccounts\Tests\TestCase;

/**
 * Source lint, not a class test: relative date arithmetic on a local time is
 * wall-clock arithmetic, so across a DST fall-back '+60 seconds' can add
 * 3660 s (ACC-3716). Expiry times must be computed on Unix timestamps.
 *
 * It reads PHP tokens: modify(), date_modify(), date_add(), date_sub(),
 * date_interval_create_from_date_string() and DateInterval are refused, and so
 * is a string literal holding a relative date ('+1 hour', '1 day ago',
 * 'tomorrow', "$date +1 day"...) passed as an argument of a DateTime /
 * DateTimeImmutable constructor, date_create() or strtotime(), directly or
 * through sprintf(). Literals of other nested calls and array keys are ignored.
 *
 * Best effort, it misses:
 * - a relative date reaching these calls through a variable, a constant or
 *   another function (new \DateTime($relative));
 * - ->add($interval) / ->sub($interval) with an interval built elsewhere.
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
        $srcDir = realpath(__DIR__ . '/../../../../src');
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
            foreach ($this->findRelativeDateArithmetic((string) file_get_contents($file->getPathname())) as $line) {
                $offenders[] = $relativePath . ':' . $line;
            }
        }

        $this->assertSame([], $offenders, 'Compute dates on Unix timestamps, e.g. new \DateTime(\'@\' . ($now + $seconds))');
    }

    /**
     * @test
     */
    public function itShouldStillMatchTheAllowedOccurrences()
    {
        foreach ($this->allowed as $relativePath) {
            $this->assertNotEmpty(
                $this->findRelativeDateArithmetic((string) file_get_contents(__DIR__ . '/../../../../src/' . $relativePath)),
                $relativePath . ' no longer needs to be allowed'
            );
        }
    }

    /**
     * @test
     */
    public function itShouldMatchRelativeDateArithmetic()
    {
        $relative = [
            '$date->modify(\'+1 hour\');',
            'date_modify($date, "+1 hour");',
            'date_add($date, $interval);',
            '$date->add(new \DateInterval(\'PT1H\'));',
            'new \DateTime(\'+\' . $seconds . \' seconds\');',
            'new DateTimeImmutable("-5 seconds");',
            'new \DateTime(\'now +1 hour\');',
            'new \DateTime(\'now + 1 hour\');',
            'new \DateTime(sprintf(\'+%d seconds\', $seconds));',
            'new \DateTime(\'1 hour\');',
            'date_create(\'next monday\');',
            'strtotime(\'tomorrow\');',
            'strtotime(\'1 day ago\');',
            'strtotime("$date +1 day");',
            'strtotime($date . \' +1 day\');',
            'strtotime(\'2026-10-25 -1 day\');',
            '$date->add(date_interval_create_from_date_string(\'1 hour\'));',
            'new \DateTime(\'@\' . strtotime(\'+1 hour\'));',
        ];
        foreach ($relative as $code) {
            $this->assertNotEmpty($this->findRelativeDateArithmetic('<?php ' . $code), $code);
        }

        $absolute = [
            'new \DateTime(\'@\' . ($now + 60));',
            'new \DateTimeImmutable(\'now\', new \DateTimeZone(\'UTC\'));',
            'new \DateTime(\'2026-10-25 02:30:00\');',
            'new \DateTime($entry->date_upd);',
            '(new \DateTime())->format(\'Y-m-d +1\');',
            '\DateTimeImmutable::createFromFormat(\'U.u\', sprintf(\'%.6F\', microtime(true)));',
            'strtotime($instant);',
            '// DateTime::modify(\'+1 hour\') on a local time',
            '$this->modify;',
            'strtotime(str_replace(\'/\', \'-\', $date));',
            'new \DateTime(implode(\'-\', [$y, $m, $d]));',
            'new \DateTime(\'now\', new \DateTimeZone(\'+02:00\'));',
            'new \DateTime(\'2026-10-25 00:30:00 +0200\');',
            'new \DateTime($row[\'last\']);',
        ];
        foreach ($absolute as $code) {
            $this->assertSame([], $this->findRelativeDateArithmetic('<?php ' . $code), $code);
        }
    }

    /**
     * @param string $code PHP source
     *
     * @return int[] lines of the offending calls
     */
    private function findRelativeDateArithmetic($code)
    {
        $tokens = array_values(array_filter(token_get_all($code), function ($token) {
            return !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
        }));

        $lines = [];
        foreach ($tokens as $i => $token) {
            if (!is_array($token) || !$this->isName($token)) {
                continue;
            }
            $name = strtolower(ltrim($token[1], '\\'));
            $isCall = isset($tokens[$i + 1]) && $tokens[$i + 1] === '(';
            $previous = $i > 0 ? $tokens[$i - 1] : null;
            $isMethod = is_array($previous) && in_array($previous[0], $this->getObjectOperators(), true);

            if ($name === 'dateinterval'
                || ($isCall && $isMethod && $name === 'modify')
                || ($isCall && !$isMethod
                    && in_array($name, ['date_modify', 'date_add', 'date_sub', 'date_interval_create_from_date_string'], true))
                || ($isCall && !$isMethod
                    && in_array($name, ['datetime', 'datetimeimmutable', 'date_create', 'date_create_immutable', 'strtotime'], true)
                    && $this->hasRelativeLiteral($tokens, $i + 1))
            ) {
                $lines[] = $token[2];
            }
        }

        return $lines;
    }

    /**
     * @param array $tokens
     * @param int $open index of the opening parenthesis
     *
     * @return bool
     */
    private function hasRelativeLiteral(array $tokens, $open)
    {
        $relative = '/^\s*[+-]|\bnow\s*[+-]|\b(tomorrow|yesterday|next|last|ago)\b'
            . '|\b\d+\s*(sec|second|min|minute|hour|day|week|fortnight|month|year)s?\b/i';

        // one entry per open parenthesis: are its string literals arguments of the date call?
        $checked = [];
        for ($i = $open, $count = count($tokens); $i < $count; ++$i) {
            $token = $tokens[$i];
            if ($token === '(') {
                $checked[] = $i === $open || (end($checked) && $this->isNameIn($tokens[$i - 1], ['sprintf', 'vsprintf']));
            } elseif ($token === ')') {
                array_pop($checked);
                if (!$checked) {
                    break;
                }
            } elseif (end($checked)
                && is_array($token)
                && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
                && !($tokens[$i - 1] === '[' && isset($tokens[$i + 1]) && $tokens[$i + 1] === ']')
                && preg_match($relative, $token[0] === T_CONSTANT_ENCAPSED_STRING ? substr($token[1], 1, -1) : $token[1])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $token
     * @param string[] $names lower case
     *
     * @return bool
     */
    private function isNameIn($token, array $names)
    {
        return is_array($token) && $this->isName($token) && in_array(strtolower(ltrim($token[1], '\\')), $names, true);
    }

    /**
     * @param array $token
     *
     * @return bool
     */
    private function isName(array $token)
    {
        // PHP >= 8.0 reads \DateTime as one name token
        return $token[0] === T_STRING
            || (defined('T_NAME_FULLY_QUALIFIED') && $token[0] === constant('T_NAME_FULLY_QUALIFIED'))
            || (defined('T_NAME_QUALIFIED') && $token[0] === constant('T_NAME_QUALIFIED'));
    }

    /**
     * @return int[]
     */
    private function getObjectOperators()
    {
        return defined('T_NULLSAFE_OBJECT_OPERATOR')
            ? [T_OBJECT_OPERATOR, constant('T_NULLSAFE_OBJECT_OPERATOR')]
            : [T_OBJECT_OPERATOR];
    }
}
