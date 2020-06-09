<?php

namespace Kohana;

/**
 * @inheritdoc
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritdoc
     */
    public function getNamespace(): string
    {
        return __NAMESPACE__ . '\\';
    }

    /**
     * @inheritdoc
     */
    public function getId(): string
    {
        return 'kohana';
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Kohana';
    }
}
