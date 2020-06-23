<?php

namespace Kohana;

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
    public function getId(): string
    {
        // TODO: Implement getId() method.
    }
}
