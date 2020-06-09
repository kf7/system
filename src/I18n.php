<?php

namespace Kohana;

use function array_merge;
use function explode;
use function implode;
use function is_array;
use function str_replace;
use function strtolower;

use const DIRECTORY_SEPARATOR;

/**
 * Internationalization (i18n) class.
 */
abstract class AbstractI18n
{
    /**
     * @var string Current language: `en-us` `ru`, etc.
     */
    protected static $language = 'en-us';

    /**
     * @var string Source language: `en-us` `ru`, etc.
     */
    protected static $sourceLanguage = 'en-us';

    /**
     * @var array An array of all available languages.
     */
    protected static $languages = ['en-us' => 'English'];

    /**
     * @var array Cache of loaded languages.
     */
    protected static $cache = [];

    /**
     * Sets the current language.
     *
     * @param string $language Target language
     * @return string
     */
    public static function setLanguage(string $language): void
    {
        if ($language !== static::$language) {
            static::$language = strtolower(str_replace([' ', '_'], '-', $language));
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
     */
    public static function setLanguages(array $languages)
    {
        if (! isset($languages[static::$sourceLanguage])) {
            throw new Exception(
                'Language list not contain source language {language}',
                ['language' => static::$sourceLanguage]
            );
        }
        static::$languages = $languages;
        if (! isset(static::$languages[static::$language])) {
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
     * @param string $source Source language.
     * @return string
     */
    public static function getText($string, ?string $language = null): string
    {
        $values = [];
        // Check if $string is array `[text, values]`
        if (is_array($string)) {
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
        $parts = explode('-', $language);
        // Loop through Paths
        foreach ([$parts[0], implode(DIRECTORY_SEPARATOR, $parts)] as $path) {
            // Load files
            // @todo replace
            $files = Kohana::findFile('i18n', $path);
            // Loop through files
            if (! empty($files)) {
                $t = [[]];
                foreach ($files as $file) {
                    // Merge the language strings into the sub table
                    $t[] = include($file);
                }
                $table[] = $t;
            }
        }
        $table = array_merge(...array_merge(...$table));
        // Cache the translation table locally
        static::$cache[$language] = $table;
        return static::$cache[$language];
    }
}
