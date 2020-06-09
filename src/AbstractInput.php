<?php

namespace Kohana;

use function array_search;

use const INPUT_COOKIE;
use const INPUT_ENV;
use const INPUT_GET;
use const INPUT_POST;
use const INPUT_REQUEST;
use const INPUT_SERVER;

/**
 * Wrapper for functions `filter_`.
 *
 * @package Kohana\System
 */
abstract class AbstractInput
{
    /**
     * @var array Aliases of input types.
     */
    protected const TYPES = [
        'post' => INPUT_POST,
        'get' => INPUT_GET,
        'request' => INPUT_REQUEST,
        'server' => INPUT_SERVER,
        'cookie' => INPUT_COOKIE,
        'env' => INPUT_ENV
    ];

    /**
     * @var int Input type set by `INPUT_` constant.
     */
    protected $type = INPUT_GET;

    /**
     * @var array An array of filter name/callback pairs, callback syntax: `fn(mixed $value): mixed;`.
     */
    protected $callbackFilters = [];

    /**
     * @var array An array of filter name/id pairs.
     */
    protected $filters = [];

    /**
     * Creates new instance.
     *
     * @param string|int $type Input type: name('get', 'post' and etc.) or `INPUT_` constant.
     */
    public function __construct($type = 'get')
    {
        $this->setType($type);
    }

    /**
     * Sets input type.
     *
     * @param string|int $type Input type name('get', 'post' and etc.) or identifier(`INPUT_` constant).
     * @return $this
     */
    public function setType($type): self
    {
        $typeId = static::TYPES[$type] ?? array_search($type, static::TYPES);
        if ($typeId === false) {
            throw new Exception('Invalid input type {type}', ['type' => $type]);
        }

        $this->type = $typeId;
        return $this;
    }
}
