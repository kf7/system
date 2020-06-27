<?php

namespace Kohana;

use RuntimeException;
use Throwable;

use function is_array;
use function strip_tags;

/**
 * Base exception.
 */
abstract class AbstractException extends RuntimeException
{
    /**
     * @var string Pattern of inline representing the exception.
     */
    public const INLINE = '{class}[{code}] {file}[{line}]: {message}';

    /**
     * Creates new exception with translated message.
     *
     * @param string|array $message The exception message or array of message and placeholders.
     * @param int $code The exception code.
     * @param Throwable $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = '', int $code = 0, Throwable $previous = null)
    {
        if (is_array($message)) {
            $message = Text::interpolate(...$message);
        }
        parent::__construct((string) $message, $code, $previous);
    }

    /**
     * Get a single line of text representing the exception.
     *
     * @return string
     */
    public function __toString()
    {
        $context = [
            'class' => static::class,
            'code' => $this->getCode(),
            'file' => Debug::path($this->getFile()),
            'line' => $this->getLine(),
            'message' => strip_tags($this->getMessage()),
        ];
        return Text::interpolate(static::INLINE, $context);
    }
}
