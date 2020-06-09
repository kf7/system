<?php

declare(strict_types=1);

namespace Kohana;

use Kohana\HTTP\Request;

use function array_replace_recursive;
use function array_slice;
use function explode;
use function filter_input;
use function filter_input_array;
use function function_exists;
use function http_build_query;
use function implode;
use function in_array;
use function is_string;
use function mb_strtolower;
use function parse_url;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;
use function rawurlencode;
use function strpos;
use function strstr;
use function strtolower;
use function substr;
use function transliterator_transliterate;
use function trim;

use const INPUT_GET;
use const INPUT_SERVER;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

/**
 * URL helper class.
 *
 * Note: You need to setup the list of trusted hosts in the `url.php` configuration file, before starting using this
 * helper class.
 */
abstract class AbstractURL
{
    /**
     * @var string Base URL.
     */
    public static $baseUrl = '';

    /**
     * Gets the base URL to the application. To specify a protocol, provide the protocol as a string or request object.
     * If a protocol is used, a complete URL will be generated using the `$_SERVER['HTTP_HOST']` variable, which will
     * be validated against RFC 952 and RFC 2181, as well as against the list of trusted hosts you have set in the
     * `url.php` configuration file.
     *
     * @param mixed $protocol Protocol string, `HTTP\Request` instance or TRUE.
     * @param bool $indexFile Add index file to URL?
     * @param string|null $subdomain Sub-domain name.
     * @return string
     * @throws KohanaException
     */
    public static function base($protocol = null, bool $indexFile = false, ?string $subdomain = null): string
    {
        // Start with the configured base URL.
        $baseUrl = static::$baseUrl;

        if ($protocol === true) {
            // Use the initial request to get the protocol
            $protocol = Request::getInitial();
        }
        if ($protocol instanceof Request) {
            if (! $protocol->isSecure()) {
                // Use the current protocol
                [$protocol] = explode('/', strtolower($protocol->protocol()));
            } else {
                $protocol = 'https';
            }
        }
        if (! $protocol) {
            // Use the configured default protocol
            $protocol = parse_url($baseUrl, PHP_URL_SCHEME);
        }
        if ($indexFile && ! empty(Kohana::$indexFile)) {
            // Add the index file to the URL
            $baseUrl .= Kohana::$indexFile . '/';
        }
        if (is_string($protocol)) {
            $port = parse_url($baseUrl, PHP_URL_PORT);
            if ($port) {
                // Found a port, make it usable for the URL
                $port = ':' . $port;
            }
            $host = parse_url($baseUrl, PHP_URL_HOST);
            if ($host) {
                // Remove everything but the path from the URL
                $baseUrl = parse_url($baseUrl, PHP_URL_PATH);
            } else {
                // Attempt to use HTTP_HOST and fallback to SERVER_NAME
                $host = filter_input(INPUT_SERVER, 'HTTP_HOST') ?: filter_input(INPUT_SERVER, 'SERVER_NAME');
            }
            // If subdomain passed, then prepend to host or replace existing subdomain
            if ($subdomain) {
                if (strstr($host, '.') === false) {
                    $host = $subdomain . '.' . $host;
                } else {
                    // Get the domain part of host eg. example.com, then prepend subdomain
                    $host = $subdomain . '.' . implode('.', array_slice(explode('.', $host), -2));
                }
            }
            // make host lowercase
            $host = strtolower($host);
            // check that host does not contain forbidden characters (see RFC 952 and RFC 2181)
            // use `preg_replace()` instead of `preg_match()` to prevent DoS attacks with long host names.
            if ($host && preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host) !== '') {
                throw new KohanaException('Invalid host {host}', ['host' => $host]);
            }
            // Validate host, see if it matches trusted hosts
            if (! static::isTrustedHost($host)) {
                throw new KohanaException('Untrusted host {host}', ['host' => $host]);
            }
            // Add the protocol and domain to the base URL
            $baseUrl = $protocol . '://' . $host . $port . $baseUrl;
        }
        return $baseUrl;
    }

    /**
     * Fetches an absolute site URL based on a URI segment.
     *
     * @param string $uri Site URI to convert.
     * @param mixed $protocol Protocol string or `HTTP\Request` instance to use protocol from.
     * @param bool $indexFile Include the index file in the URL,
     * @param string $subdomain Sub-domain name.
     * @return string
     */
    public static function site($uri = '', $protocol = null, bool $indexFile = false, ?string $subdomain = null): string
    {
        // Chop off possible scheme, host, port, user and pass parts
        $path = preg_replace('~^[-\w\d+.]++://[^/]++/?~', '', trim((string) $uri, '/ '));
        if (! UTF8::isAscii($path)) {
            // Encode all non-ASCII characters, as per RFC 1738
            $path = preg_replace_callback('~([^/#]+)~', [static::class, 'siteCallback'], $path);
        }
        // Concat the URL
        return static::base($protocol, $indexFile, $subdomain) . $path;
    }

    /**
     * Callback used for encoding all non-ASCII characters, as per RFC 1738.
     *
     * @param array $matches An array of matches from function `preg_replace_callback()`.
     * @return string Encoded string.
     * @return string
     */
    protected static function siteCallback(array $matches): string
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Merges the current GET parameters with an array of new or overloaded parameters and returns the resulting query
     * string. Typically you would use this when you are sorting query results or something similar.
     *
     * Note: parameters with a NULL value are left out.
     *
     * @param array $params GET parameters.
     * @param bool $useGlobal Include `$_GET` parameters.
     * @return string
     */
    public static function query(array $params = [], bool $useGlobal = true): string
    {
        if ($useGlobal) {
            // Merge the current and new parameters
            $params = array_replace_recursive(filter_input_array(INPUT_GET), $params);
        }
        if (empty($params)) {
            // No query parameters
            return '';
        }
        // Note: `http_build_query()` returns an empty string for a params array with only NULL values
        $query = http_build_query($params, '', '&');
        // Don't prepend '?' to an empty string
        return $query === '' ? '' : '?' . $query;
    }

    /**
     * Converts a phrase to a URL-safe title, example: 'My blog' to 'my-blog'.
     *
     * @param string $title Phrase to convert.
     * @param string $separator Word separator (any single character)
     * @param bool $asciiOnly Transliterate to ASCII?
     * @return string
     */
    public static function title(string $title, string $separator = '-', bool $asciiOnly = false): string
    {
        if ($asciiOnly) {
            // Transliterate non-ASCII characters
            if (function_exists('transliterator_transliterate')) {
                $title = transliterator_transliterate('Any-Latin; Latin-ASCII', $title);
            } else {
                $title = UTF8::transliterateToAscii($title);
            }
            // Remove all characters that are not the separator, a-z, A-Z, 0-9, or whitespace.
            $pattern = '![^' . preg_quote($separator) . '\w\d\s]+!';
            $title = preg_replace($pattern, '', strtolower($title));
        } else {
            // Remove all characters that are not the separator, letters, numbers, or whitespace.
            $pattern = '![^' . preg_quote($separator) . '\pL\pN\s]+!u';
            $title = preg_replace($pattern, '', mb_strtolower($title, 'UTF-8'));
        }
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);
        // Trim separators from the beginning and end
        return trim($title, $separator);
    }

    /**
     * Returns check is absolute URL.
     *
     * @param object|string $url URL or URI.
     * @return bool
     */
    public static function isAbsolute($url): bool
    {
        $url = (string) $url;
        return ! in_array($url, ['', '/'], true) && substr($url, 0, 5) !== 'data:' && strpos($url, '//') !== false;
    }

    /**
     * Test if given $host should be trusted.
     *
     * @param string $host Host name.
     * @param array $trustedHosts Optional list of trusted hosts(RegExp's).
     * @return bool
     */
    public static function isTrustedHost(string $host, ?array $trustedHosts = null): bool
    {
        // If list of trusted hosts is not directly provided, read from configuration.
        if ($trustedHosts === null) {
            $trustedHosts = Config::load('url')->get('trusted_hosts');
        }
        foreach ($trustedHosts as $host) {
            // Make sure we fully match the trusted hosts.
            if (preg_match('#^' . $host . '$#uD', $host)) {
                return true;
            }
        }
        return false;
    }
}
