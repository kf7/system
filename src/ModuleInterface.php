<?php

namespace Kohana;

/**
 * Module manager.
 */
interface ModuleInterface
{
    /**
     * @var string Source directory.
     */
    public const SOURCE_DIR = 'src';

    /**
     * @var string Unit tests directory.
     */
    public const TEST_DIR = 'tests';

    /**
     * @var string Views directory.
     */
    public const VIEW_DIR = 'views';

    /**
     * @var string Configuration directory.
     */
    public const CONFIG_DIR = 'config';

    /**
     * @var string Translates and messages directory.
     */
    public const I18N_DIR = 'i18n';

    /**
     * @var string Assets (JS, CSS and etc.) directory.
     */
    public const MEDIA_DIR = 'media';

    /**
     * @var array Common directories of module.
     */
    public const DIRS = [
        self::SOURCE_DIR,
        self::TEST_DIR,
        self::VIEW_DIR,
        self::CONFIG_DIR,
        self::I18N_DIR,
        self::MEDIA_DIR,
    ];

    /**
     * Returns path to module directory.
     *
     * @return string MUST end with `/`.
     */
    public function getPath(): string;

    /**
     * Returns base namespace of module's classes.
     *
     * @return string MUST end with `\\`.
     */
    public function getNamespace(): string;

    /**
     * Returns module identifier.
     *
     * @return string Identifier MUST be `alnum` string.
     */
    public function getId(): string;

    /**
     * Returns module name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns module version.
     *
     * @return string Version MUST follow the format of `%d.%d.%d`.
     */
    public function getVersion(): string;

    /**
     * Returns module routes.
     *
     * @return array MUST be instances of `Kohana\HTTP\RouteInterface`
     */
    public function getRoutes(): array;
}
