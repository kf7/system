<?php

namespace Kohana;

use DirectoryIterator;

/**
 * Module manager.
 */
abstract class AbstractModule implements ModuleInterface
{
    /**
     * @inheritdoc
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getRoutes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getDirectoryIterator(): DirectoryIterator
    {
        return new DirectoryIterator($this->getPath());
    }
}
