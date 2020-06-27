<?php

namespace Kohana\HTTP;

use Kohana\Exception as KohanaException;
use Throwable;

class Exception extends KohanaException
{
    /**
     * Exception constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
