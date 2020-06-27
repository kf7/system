<?php

namespace Kohana;

use const E_ERROR;
use const E_PARSE;
use const E_USER_ERROR;
use const PHP_OS_FAMILY;

/**
 * Contains the most low-level helpers methods:
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 */
abstract class AbstractKohana
{
    /**
     * @var int Environment type: production.
     */
    public const ENV_PRODUCTION = 1;

    /**
     * @var int Environment type: staging.
     */
    public const ENV_STAGING = 2;

    /**
     * @var int Environment type: testing.
     */
    public const ENV_TESTING = 3;

    /**
     * @var int Environment type: development.
     */
    public const ENV_DEVELOPMENT = 4;

    /**
     * @var bool True if Kohana is running on OS Windows.
     */
    public const IS_WINDOWS = PHP_OS_FAMILY === 'Windows';
    /**
     * @var array Types of errors to display at shutdown.
     */
    public $shutdownErrors = [E_PARSE, E_ERROR, E_USER_ERROR];
    /**
     * @var int Current environment type.
     */
    private $environment = self::ENV_DEVELOPMENT;
    /**
     * @var bool Whether to use caching.
     */
    private $caching = false;
    /**
     * @var bool Whether to enable profiling.
     */
    private $profiling = true;
    /**
     * @var bool Enable catching and displaying PHP errors and exceptions.
     */
    private $errors = true;
    /**
     * @var object logging object
     */
    private $log;

    /**
     * @var object config object
     */
    private $config;

    /**
     * Creates new instance.
     *
     * @param array $settings Instance settings
     */
    public function __construct(array $settings)
    {
    }

    /**
     * Returns current environment type.
     *
     * @return int
     */
    public function getEnvironment(): int
    {
        return $this->environment;
    }

    /**
     * Sets current environment type.
     *
     * @param int $environment Environment type, `self::ENV_` constant
     */
    public function setEnvironment(int $environment): void
    {
        $this->environment = $environment;
    }
}
