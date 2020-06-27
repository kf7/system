<?php

namespace Kohana;

/**
 * Kohana's system/core module
 */
class Module extends AbstractModule
{
    /**
     * @var string Path to base directory
     */
    private $path;

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        if ($this->path === null) {
            $this->path = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        }
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getNamespace(): string
    {
        return __NAMESPACE__ . '\\';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return 'system';
    }
}
