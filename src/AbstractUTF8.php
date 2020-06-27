<?php

namespace Kohana;

use function implode;
use function is_array;
use function is_string;
use function mb_convert_encoding;
use function mb_substitute_character;
use function preg_match;
use function preg_replace;

/**
 * Provides multi-byte aware replacement string functions.
 */
abstract class AbstractUTF8
{
    /**
     * Recursively cleans arrays, objects, and strings. Removes ASCII control codes and converts to the requested
     * charset while silently discarding incompatible characters.
     *
     * @param string|array $variable variable to clean
     * @param null|string $charset character set, defaults to KO7::$charset
     * @return mixed
     * @noinspection OffsetOperationsInspection
     */
    public static function clean($variable, ?string $charset = null)
    {
        if (!$variable) {
            return $variable;
        }
        if (!$charset) {
            // use the application character set
            $charset = Module::$charset;
        }
        if (is_array($variable)) {
            $vars = [];
            foreach ($variable as $key => $value) {
                $key = static::clean((string)$key, $charset);
                $vars[$key] = static::clean($value, $charset);
            }
            $variable = $vars;
        } elseif (is_string($variable)) {
            $variable = static::stripAsciiCtrl($variable);
            if (!static::isAscii($variable)) {
                // temporarily save the value into a variable
                $substituteCharacter = mb_substitute_character();
                // convert encoding, this is expensive, used when value is not ASCII
                $variable = mb_convert_encoding($variable, $charset, $charset);
                // reset value back to the original setting
                mb_substitute_character($substituteCharacter);
            }
        }
        return $variable;
    }

    /**
     * Strips out device control codes in the ASCII range.
     *
     * @param string $str string to clean
     * @return string
     */
    public static function stripAsciiCtrl(string $str): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str);
    }

    /**
     * Tests whether a string contains only 7-bit ASCII bytes. This is used to determine when to use native functions or
     * UTF-8 functions.
     *
     * @param string|string[] $str string(s) to check
     * @return bool
     */
    public static function isAscii($str): bool
    {
        if (is_array($str)) {
            $str = implode('', $str);
        }

        return !preg_match('/[^\x00-\x7F]/S', $str);
    }

    /**
     * Strips out all non-7bit ASCII bytes.
     *
     * @param string $str string to clean
     * @return string
     */
    public static function stripNonAscii(string $str): string
    {
        return preg_replace('/[^\x00-\x7F]+/S', '', $str);
    }
}
