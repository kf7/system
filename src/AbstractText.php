<?php

namespace Kohana7;

use function sprintf;
use function strtr;

/**
 * Text helper.
 */
abstract class AbstractText
{
    /**
     * @var string Pattern of message placeholder.
     */
    public const PLACEHOLDER = '{%s}';

    /**
     * Interpolates context values into the message placeholders.
     *
     * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message
     *
     * @param string $message a message with brace-delimited placeholder names
     * @param array $context a context array of placeholder names => replacement values
     * @return string
     */
    public static function interpolate($message, array $context = []): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $value) {
            $replace[sprintf(static::PLACEHOLDER, $key)] = (string) $value;
        }
        // interpolate replacement values into the message and return
        return strtr((string) $message, $replace);
    }
}
