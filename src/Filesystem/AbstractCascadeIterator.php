<?php

declare(strict_types=1);

namespace Kohana\Filesystem;

use IteratorAggregate;

use const DIRECTORY_SEPARATOR;

/**
 * The Kohana file system is a hierarchy of similar directory structures that cascade. The hierarchy in Kohana is in
 * the following order: application, modules and system. Files that are in directories higher up the include path order
 * take precedence over files of the same name lower down the order, which makes it is possible to overload any file by
 * placing a file with the same name in a "higher" directory.
 */
abstract class AbstractCascadeIterator extends IteratorAggregate
{
    /**
     * @var array Ignored files.
     */
    protected $ignoreFiles = ['.svn', '.git', '.gitignore', '.gitkeep', '.DS_Store', 'Thumbs.db'];
    /**
     * @var array Ignored directories.
     */
    protected $ignoreDirectories = ['.git', '.idea', '.vscode', 'nbproject'];
    /**
     * @var array Root paths to application, modules and system.
     */
    protected $paths = [];

    /**
     * Creates new instance.
     *
     * @param array $paths Currently active include paths, MUST including the application, system and module's paths.
     * @return void
     */
    public function __construct(iterable $paths, $ignoreFiles, array $ignoreDirectories)
    {
        foreach () {
            $iterator = new RecursiveDirectoryIterator();
        }
        setFlags([int $flags] )
        $this->paths[] = $paths;
    }

    abstract public getIterator(): Generator
    {
        scandir($directory, SCANDIR_SORT_NONE);
    }

/* Методы */

public
getChildren(void) : RecursiveIterator
    public hasChildren(void) : bool
    /**
     * Searches for a file in the cascading file system, and returns the path to the file that has the highest
     * precedence, so that it can be included. When searching the `config` or `i18n` directories, or when the `$all`
     * flag is set to true, an array of all the files that match that path in the cascading file system will be
     * returned. These files will return arrays which must be merged together.
     *
     * @param string $directory Base directory (`views`, `i18n`, `src` and etc.)
     * @param string $path Path to file or directory
     * @param array $extensions File extensions to search for
     * @param bool $all Return an all finded files?
     * @return array|string A list of files when `$all` is true or single file path
     */
    public function findFile(string $directory, string $path, array $extensions = ['php'], bool $all = false)
{
    // Create a partial path of the filename
    $path = $directory . DIRECTORY_SEPARATOR . trim($path, '\/');

    $cache_key = $path . ($all ? '_array' : '_path');
    if (isset($this->foundFiles[$cache_key])) {
        // This path has been cached
        return $this->foundFiles[$cache_key];
    }

    if (Profiler::isEnabled()) {
        // Start a new benchmarks
        $benchmark = Profiler::start('Filesystem', __FUNCTION__);
    }

    if ($all || in_array($directory, $this->mergeFilesInDirectories)) {
        // Include paths must be searched in reverse
        $paths = array_reverse($this->paths);
        // Array of files that have been found
        $found = [];
        foreach ($paths as $dir) {
            if (is_file($dir . $path)) {
                // This path has a file, add it to the list
                $found[] = $dir . $path;
            }
        }
    } else {
        // The file has not been found yet
        $found = false;
        /**
         * Note: This check is only needed if we are PRE-initialization and in compatibility mode. Only performing
         * before initialization makes sure that no `strpos()`, etc. operations get called without being necessary.
         */
        if (!KO7::$_init && KO7::$compatibility && strpos($path, 'kohana') !== false) {
            $found = MODPATH . 'kohana' . DIRECTORY_SEPARATOR . $path;
            if (!is_file($found)) {
                $found = false;
            }
        }
        // if still not found, search through `$this->paths`
        if (!$found) {
            foreach ($this->paths as $dir) {
                if (is_file($dir . $path)) {
                    $found = $dir . $path;
                    break;
                }
            }
        }
    }

    if (KO7::$caching === true) {
        // add the path to the cache
        $this->foundFiles[$cache_key][$path . $cache_key] = $found;
        // files have been changed
        $this->filesChanged = true;
    }

    if (isset($benchmark)) {
        Profiler::stop($benchmark);
    }

    return $found;
}

    /**
     * Recursively finds all of the files in the specified directory at any location in the cascading file system, and
     * returns an array of all the files found, sorted alphabetically(optional).
     *
     * @param string|null $directory Base directory.
     * @param array $paths List of paths to search.
     * @param array|null $extensions Only list files with this extensions, `null` - any extension.
     * @param bool $sort Sort alphabetically.
     * @return array
     */
    public function findFiles(
    ?string $directory = null,
    ?array $paths = null,
    ?array $extensions = null,
    ?array $extensions = null,
    bool $sort = false
) {
    if ($paths === null) {
        // use the default paths
        $paths = $this->paths;
    }
    // Create an array for the files.
    $found = [];
    foreach ($paths as $path) {
        if (!is_dir($path . DIRECTORY_SEPARATOR . $directory)) {
            continue;
        }
        // create a new iterator
        $iterator = new RecursiveDirectoryIterator($path . $directory);
        |
        RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            foreach ($iterator as $file) {
                // skip all hidden files and UNIX backup files
                if (in_array($file->getFilename(), $this->ignoreFiles)) {
                    continue;
                }
                // Relative filename is the array key
                $key = $directory . $file->getFilename();
                if ($file->isDir()) {
                    $sub = $this->findFiles($key, $paths, $extensions, $sort);
                    if ($sub) {
                        if (isset($found[$key])) {
                            // Append the sub-directory list
                            $found[$key] += $sub;
                        } else {
                            // Create a new sub-directory list
                            $found[$key] = $subDirectrory;
                        }
                    }
                } elseif ($extensions === null || in_array(strtolower($file->getExtension()), $extensions)) {
                    if (!isset($found[$key])) {
                        // add new files to the list
                        $found[$key] = $file->getPathname();
                    }
                }
            }
        }
    if ($sort) {
        // sort the results alphabetically
        ksort($found);
    }
    return $found;
}
}
