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

/*
 * Removes "implicitly nullable" parameter types (`Type $x = null`), deprecated
 * since PHP 8.4, from the bundled vendor libraries.
 *
 * The PHP 8.4 fix (`?Type $x = null`) is PHP 7.1+ syntax and the module must
 * still run on PHP 5.6, so the native type is dropped instead (`$x = null`);
 * the docblocks keep documenting it. The whole tree is patched in one pass, so
 * a parent method and its overrides always end up with the same signature.
 *
 * Usage: php scripts/patch-implicit-nullable.php <dir> [<dir>...]
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php {$argv[0]} <dir> [<dir>...]\n");
    exit(1);
}

$typeTokens = [T_STRING, T_NS_SEPARATOR, T_ARRAY, T_CALLABLE];
foreach (['T_NAME_QUALIFIED', 'T_NAME_FULLY_QUALIFIED', 'T_NAME_RELATIVE'] as $name) {
    if (defined($name)) {
        $typeTokens[] = constant($name);
    }
}

/**
 * @param array $tokens
 * @param int $i
 * @param int $step
 *
 * @return int index of the next (or previous) non-whitespace/comment token, -1 if none
 */
function skipBlanks(array $tokens, $i, $step)
{
    for ($i += $step; isset($tokens[$i]); $i += $step) {
        if (!is_array($tokens[$i]) || !in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            return $i;
        }
    }

    return -1;
}

/**
 * @param string $code
 * @param array $typeTokens
 * @param int $count
 *
 * @return string
 */
function patchCode($code, array $typeTokens, & $count)
{
    $tokens = token_get_all($code);
    $remove = [];
    $inParams = false;
    $depth = 0;

    foreach ($tokens as $i => $token) {
        if (!$inParams) {
            if (is_array($token) && $token[0] === T_FUNCTION) {
                $open = skipBlanks($tokens, $i, 1);
                // skip the function name / by-reference marker
                while ($open !== -1 && $tokens[$open] !== '(') {
                    $open = skipBlanks($tokens, $open, 1);
                }
                if ($open !== -1) {
                    $inParams = true;
                    $depth = 0;
                }
            }
            continue;
        }

        if ($token === '(') {
            ++$depth;
        } elseif ($token === ')') {
            if (--$depth === 0) {
                $inParams = false;
            }
        } elseif ($depth === 1 && is_array($token) && $token[0] === T_VARIABLE) {
            $eq = skipBlanks($tokens, $i, 1);
            $null = $eq === -1 ? -1 : skipBlanks($tokens, $eq, 1);
            if ($eq === -1 || $tokens[$eq] !== '='
                || $null === -1 || !is_array($tokens[$null]) || strtolower($tokens[$null][1]) !== 'null'
            ) {
                continue;
            }
            $next = skipBlanks($tokens, $null, 1);
            if ($next === -1 || ($tokens[$next] !== ',' && $tokens[$next] !== ')')) {
                continue;
            }

            // walk back over the type (by-ref / variadic markers sit between type and variable)
            $end = skipBlanks($tokens, $i, -1);
            while ($end !== -1 && ($tokens[$end] === '&' || (is_array($tokens[$end]) && $tokens[$end][0] === T_ELLIPSIS))) {
                $end = skipBlanks($tokens, $end, -1);
            }
            $start = $end;
            while ($start >= 0 && is_array($tokens[$start]) && in_array($tokens[$start][0], $typeTokens, true)) {
                --$start;
            }
            ++$start;
            if ($start > $end) {
                continue; // untyped
            }
            $before = skipBlanks($tokens, $start, -1);
            if ($before !== -1 && ($tokens[$before] === '?' || $tokens[$before] === '|')) {
                continue; // already explicit (?Type) or union type
            }
            // drop the type and the blank that follows it
            for ($k = $start; $k <= $end + 1; ++$k) {
                if ($k > $end && !(is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE)) {
                    break;
                }
                $remove[$k] = true;
            }
            ++$count;
        }
    }

    if (!$remove) {
        return $code;
    }

    $out = '';
    foreach ($tokens as $i => $token) {
        if (!isset($remove[$i])) {
            $out .= is_array($token) ? $token[1] : $token;
        }
    }

    return $out;
}

$total = 0;
foreach (array_slice($argv, 1) as $dir) {
    $files = new RegexIterator(
        new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)),
        '/\.php$/'
    );
    foreach ($files as $file) {
        $path = $file->getPathname();
        $code = file_get_contents($path);
        $count = 0;
        $patched = patchCode($code, $typeTokens, $count);
        if ($count > 0) {
            file_put_contents($path, $patched);
            echo "  {$path}: {$count} parameter(s)\n";
            $total += $count;
        }
    }
}
echo "{$total} implicitly nullable parameter(s) patched\n";
