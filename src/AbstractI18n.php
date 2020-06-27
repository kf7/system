<?php

namespace Kohana;

use function array_merge;
use function explode;
use function is_array;
use function str_replace;

/**
 * Internationalization (i18n) class.
 */
abstract class AbstractI18n
{
    /**
     * @var string Current language.
     */
    protected static $language = 'en_US';

    /**
     * @var string Source language.
     */
    protected static $sourceLanguage = 'en_US';

    /**
     * @var string[] An array of all available languages.
     */
    protected static $languages = ['en_US' => 'English'];

    /**
     * @var array Cache of loaded languages.
     */
    protected static $cache = [];

    /**
     * Sets the current language.
     *
     * @param string $language Target language
     * @return void
     */
    public static function setLanguage(string $language): void
    {
        if ($language !== static::$language) {
            static::$language = str_replace([' ', '-', '.'], '_', $language);
        }
    }

    /**
     * Returns the current language.
     *
     * @return string
     */
    public static function getLanguage(): string
    {
        return static::$language;
    }

    /**
     * Returns the available languages.
     *
     * @param array $languages An array of language/name pairs.
     * @return void
     */
    public static function setLanguages(array $languages): void
    {
        if (!isset($languages[static::$sourceLanguage])) {
            throw new Exception(
                [
                    'Language list not contain source language {language}',
                    ['language' => static::$sourceLanguage],
                ]
            );
        }
        static::$languages = $languages;
        if (!isset(static::$languages[static::$language])) {
            static::$language = key(static::$languages);
        }
    }

    /**
     * Returns the available languages.
     *
     * @return array
     */
    public static function getLanguages(): array
    {
        return static::$languages;
    }

    /**
     * Returns translation of a string. If no translation exists, the original string will be returned. No parameters
     * are replaced.
     *
     * @param string|array $string Text to translate or array `[text, placeholders]`.
     * @param string|null $language Current language.
     * @return string
     */
    public static function getText($string, ?string $language = null): string
    {
        $values = [];
        // Check if $string is array `[text, values]`
        if (is_array($string)) {
            /** @var string $string */
            [$string, $values] = $string;
        }
        // Set target language if not set
        if (! $language) {
            $language = static::$language;
        }
        // Load table only if source language does not match target language
        if (static::$sourceLanguage !== $language) {
            // Load the translation table for this language
            $table = static::load($language);
            // Return the translated string if it exists
            $string = $table[$string] ?? $string;
        }
        return $values ? Text::interpolate($string, $values) : $string;
    }

    /**
     * Returns the translation table for a given language.
     *
     * @param string $language language to load
     * @return array
     */
    public static function load(string $language): array
    {
        if (isset(static::$cache[$language])) {
            return static::$cache[$language];
        }
        // New translation table
        $table = [[]];
        // Split the language: language, region, locale, etc
        $parts = explode('_', $language);
        // Loop through Paths
        foreach ([$parts[0], $language] as $path) {
            // Load files
            // @todo replace
            $files = Module::findFiles('i18n', $path);
            // Loop through files
            if ($files) {
                $t = [[]];
                foreach ($files as $file) {
                    // Merge the language strings into the sub table
                    $t[] = static::loadFile($file);
                }
                $table[] = $t;
            }
        }
        $table = array_merge(...array_merge(...$table));
        // cache the translation table locally
        static::$cache[$language] = $table;
        return static::$cache[$language];
    }

    /**
     * @param string $path Path to localization file
     * @return array
     */
    protected static function loadFile(string $path): array
    {
        return (array)require($path);
    }
}
