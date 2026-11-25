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
// PHP 8.1+ tokenizes the by-reference "&" of a parameter instead of returning a bare "&"
$byRefTokens = ['&'];
foreach (['T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG', 'T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG'] as $name) {
    if (defined($name)) {
        $byRefTokens[] = constant($name);
    }
}

/**
 * @param array $tokens
 * @param int $i
 * @param array $expected token ids and/or single-char tokens
 *
 * @return bool
 */
function tokenIs(array $tokens, $i, array $expected)
{
    if (!isset($tokens[$i])) {
        return false;
    }
    $token = $tokens[$i];

    return in_array(is_array($token) ? $token[0] : $token, $expected, true);
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
        if (!tokenIs($tokens, $i, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
            return $i;
        }
    }

    return -1;
}

/**
 * @param array $tokens
 * @param int $function index of a T_FUNCTION token
 *
 * @return int[] indexes of the parameter variables of that function
 */
function parameterVariables(array $tokens, $function)
{
    // skip the function name / by-reference marker; `use function Foo\bar;` has no parameters
    $i = $function;
    do {
        $i = skipBlanks($tokens, $i, 1);
        if (tokenIs($tokens, $i, [';', '{'])) {
            return [];
        }
    } while ($i !== -1 && $tokens[$i] !== '(');

    $variables = [];
    for ($depth = 0; isset($tokens[$i]); ++$i) {
        if ($tokens[$i] === '(') {
            ++$depth;
        } elseif ($tokens[$i] === ')' && --$depth === 0) {
            break;
        } elseif ($depth === 1 && tokenIs($tokens, $i, [T_VARIABLE])) {
            $variables[] = $i;
        }
    }

    return $variables;
}

/**
 * @param array $tokens
 * @param int $variable
 *
 * @return bool whether the parameter default value is exactly `null`
 */
function hasNullDefault(array $tokens, $variable)
{
    $equal = skipBlanks($tokens, $variable, 1);
    if (!tokenIs($tokens, $equal, ['='])) {
        return false;
    }
    $value = skipBlanks($tokens, $equal, 1);

    return tokenIs($tokens, $value, [T_STRING])
        && strtolower($tokens[$value][1]) === 'null'
        && tokenIs($tokens, skipBlanks($tokens, $value, 1), [',', ')']);
}

/**
 * @param array $tokens
 * @param int $variable
 * @param array $typeTokens
 * @param array $byRefTokens
 *
 * @return array|null [first, last] token indexes of the parameter's implicit nullable type, null if none
 */
function typeRange(array $tokens, $variable, array $typeTokens, array $byRefTokens)
{
    $end = skipBlanks($tokens, $variable, -1);
    if (tokenIs($tokens, $end, $byRefTokens)) {
        $end = skipBlanks($tokens, $end, -1);
    }
    if (!tokenIs($tokens, $end, $typeTokens)) {
        return null; // untyped
    }
    $start = $end;
    while (tokenIs($tokens, $start - 1, $typeTokens)) {
        --$start;
    }
    if (tokenIs($tokens, skipBlanks($tokens, $start, -1), ['?', '|'])) {
        return null; // already explicit (?Type) or union type
    }

    return [$start, $end];
}

/**
 * @param array $tokens
 * @param array $typeTokens
 * @param array $byRefTokens
 *
 * @return array [first, last] token indexes of each implicit nullable type
 */
function implicitNullableTypes(array $tokens, array $typeTokens, array $byRefTokens)
{
    $types = [];
    foreach (array_keys($tokens) as $i) {
        if (!tokenIs($tokens, $i, [T_FUNCTION])) {
            continue;
        }
        foreach (parameterVariables($tokens, $i) as $variable) {
            $type = hasNullDefault($tokens, $variable) ? typeRange($tokens, $variable, $typeTokens, $byRefTokens) : null;
            if ($type !== null) {
                $types[] = $type;
            }
        }
    }

    return $types;
}

/**
 * @param array $tokens
 * @param array $types [first, last] token indexes to drop, with the blank that follows each
 *
 * @return string
 */
function removeTypes(array $tokens, array $types)
{
    $remove = [];
    foreach ($types as $type) {
        list($start, $end) = $type;
        if (tokenIs($tokens, $end + 1, [T_WHITESPACE])) {
            ++$end;
        }
        $remove += array_fill_keys(range($start, $end), true);
    }

    $code = '';
    foreach ($tokens as $i => $token) {
        if (!isset($remove[$i])) {
            $code .= is_array($token) ? $token[1] : $token;
        }
    }

    return $code;
}

$total = 0;
foreach (array_slice($argv, 1) as $dir) {
    $files = new RegexIterator(
        new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)),
        '/\.php$/'
    );
    foreach ($files as $file) {
        $path = $file->getPathname();
        $tokens = token_get_all(file_get_contents($path));
        $types = implicitNullableTypes($tokens, $typeTokens, $byRefTokens);
        if ($types) {
            file_put_contents($path, removeTypes($tokens, $types));
            echo '  ' . $path . ': ' . count($types) . " parameter(s)\n";
            $total += count($types);
        }
    }
}
echo "{$total} implicitly nullable parameter(s) patched\n";
