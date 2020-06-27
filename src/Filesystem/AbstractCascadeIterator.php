<?php

namespace Kohana\Filesystem;

use Countable;
use FilterIterator;
use Iterator;
use Kohana\ModuleInterface;

/**
 * Iterator of Kohana's cascading filesystem
 */
abstract class AbstractCascadeIterator extends FilterIterator implements Countable
{
    /**
     * @var ModuleInterface[] Modules
     */
    protected $modules;

    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->iterator);
    }

    /**
     * Create new instance.
     *
     * @param ModuleInterface[] $modules ModuleInterface instances
     */
    public function __construct(ModuleInterface ...$modules)
    {
        $this->modules = $modules;
        //$iterator = new \DirectoryIterator($path);
        //parent::__construct($iterator);
    }
}
