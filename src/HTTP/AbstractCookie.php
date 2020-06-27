<?php

namespace Kohana\HTTP;

use function explode;
use function filter_has_var;
use function filter_input;
use function hash_hmac;
use function setcookie;
use function strlen;
use function time;

use const FILTER_FLAG_NO_ENCODE_QUOTES;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;
use const INPUT_COOKIE;
use const PHP_VERSION_ID;

/**
 * HTTP COOKIE helper.
 */
abstract class AbstractCookie
{
    /**
     * @var string Cookies are allowed to be sent with top-level navigations and will be sent along with GET request
     *     initiated by third party website.
     */
    public const SAMESITE_LAX = 'Lax';

    /**
     * @var string Cookies will only be sent in a first-party context and not be sent along with requests initiated by
     *     third party sites.
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * @var string Cookies will be sent in all contexts, i.e sending cross-origin is allowed.
     */
    public const SAMESITE_NONE = 'None';

    /**
     * @var string Separate the salt and the value.
     */
    protected const SALT_SEPARATOR = '~';

    /**
     * @var string Magic salt to add to the cookie.
     */
    protected static $salt;

    /**
     * @var int Number of seconds before the cookie expires.
     */
    protected static $expiration = 0;

    /**
     * @var string Restrict the path that the cookie is available to.
     */
    protected static $path = '/';

    /**
     * @var string Restrict the domain that the cookie is available to.
     */
    protected static $domain = '';

    /**
     * @var bool Only transmit cookies over secure connections.
     */
    protected static $secure = false;

    /**
     * @var bool Only transmit cookies over HTTP, disabling JavaScript access
     */
    protected static $httpOnly = true;

    /**
     * @var string Cookie should be restricted to a first-party or same-site context.
     */
    protected static $sameSite = self::SAMESITE_STRICT;

    /**
     * Gets the value of a signed cookie. Cookies without signatures will not be returned. If the cookie signature is
     * present, but invalid, the cookie will be deleted.
     *
     * @param string $name Cookie name
     * @param mixed $default Default value to return
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        if (filter_has_var(INPUT_COOKIE, $name)) {
            // Find the position of the split between salt and contents
            $position = strlen(static::getSalt($name, ''));
            // Get the cookie value
            $cookie = filter_input(
                INPUT_COOKIE,
                $name,
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (isset($cookie[$position]) && $cookie[$position] === static::SALT_SEPARATOR) {
                // Separate the salt and the value
                [$hash, $value] = explode(static::SALT_SEPARATOR, $cookie, 2);
                if (static::getSalt($name, $value) === $hash) {
                    // Cookie signature is valid
                    return $value;
                }
                // The cookie signature is invalid, delete it
                static::delete($name);
            }
        }
        // The cookie does not exist
        return $default;
    }

    /**
     * Generates a salt string for a cookie based on the name and value.
     *
     * @param string $name name of cookie
     * @param string $value value of cookie
     * @return string
     * @throws Exception If salt is not configured
     */
    public static function getSalt(string $name, $value): string
    {
        // Require a valid salt
        if (!static::$salt) {
            throw new Exception('A valid cookie salt is required');
        }
        return hash_hmac('sha1', $name . $value . static::$salt, static::$salt);
    }

    /**
     * Sets magic salt.
     *
     * @param string $salt New value.
     * @return void
     */
    public static function setSalt(string $salt): void
    {
        if (!$salt) {
            throw new Exception('Empty cookie salt');
        }
        static::$salt = $salt;
    }

    /**
     * Deletes a cookie by making the value NULL and expiring it.
     *
     * @param string $name cookie name
     * @return bool
     */
    public static function delete(string $name): bool
    {
        // Nullify the cookie and make it expire
        return static::setCookie(
            $name,
            null,
            -86400,
            static::$path,
            static::$domain,
            static::$secure,
            static::$httpOnly,
            static::$sameSite
        );
    }

    /**
     * Proxy for the `setcookie()` function to allow mocking in unit tests so that they don't fail when headers has
     * been sent.
     *
     * @param string $name The name of the cookie.
     * @param null|string $value The value of the cookie.
     * @param int $expires The time the cookie expires.
     * @param string $path The path on the server in which the cookie will be available on.
     * @param string $domain The (sub)domain that the cookie is available to.
     * @param bool $secure Should only be transmitted over a secure HTTPS connection from the client.
     * @param bool $httpOnly Will be made accessible only through the HTTP protocol.
     * @param string $sameSite Should be restricted to a first-party or same-site context.
     * @return bool
     */
    protected static function setCookie(
        string $name,
        ?string $value,
        int $expires,
        string $path,
        string $domain,
        bool $secure,
        bool $httpOnly,
        string $sameSite
    ): bool {
        if (PHP_VERSION_ID >= 70300) {
            $options = [
                'expires' => $expires,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite,
            ];
            return setcookie($name, $value, $options);
        }
        return setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Sets a signed cookie. Note that all cookie values must be strings and no automatic serialization will be
     * performed!
     *
     * Note: by default, `self::$expiration` is 0, if you skip/pass null for the optional lifetime argument your
     * cookies will expire immediately unless you have separately configured `self::$expiration`.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie.
     * @param int $lifetime The cookie lifetime in seconds.
     * @return bool
     */
    public static function set(string $name, $value, int $lifetime = null): bool
    {
        if ($lifetime === null) {
            // Use the default expiration
            $lifetime = static::$expiration;
        }
        if ($lifetime !== 0) {
            // The expiration is expected to be a UNIX timestamp
            $lifetime += static::getTime();
        }
        // Add the salt to the cookie value
        $value = static::getSalt($name, $value) . static::SALT_SEPARATOR . $value;
        return static::setCookie(
            $name,
            $value,
            $lifetime,
            static::$path,
            static::$domain,
            static::$secure,
            static::$httpOnly,
            static::$sameSite
        );
    }

    /**
     * Proxy for the native time function - to allow mocking of time-related logic in unit tests.
     *
     * @return int
     */
    protected static function getTime(): int
    {
        return time();
    }

    /**
     * Sets expiration time.
     *
     * @param int $expiration New value.
     * @return void
     */
    public static function setExpiration(int $expiration): void
    {
        static::$expiration = $expiration;
    }

    /**
     * Sets restrict the path that the cookie is available to.
     *
     * @param string $path New value.
     * @return void
     */
    public static function setPath(string $path): void
    {
        static::$path = $path;
    }

    /**
     * Sets restrict the domain that the cookie is available to.
     *
     * @param string $domain New value.
     * @return void
     */
    public static function setDomain(string $domain): void
    {
        static::$domain = $domain;
    }

    /**
     * Sets transmit cookies over secure connections.
     *
     * @param bool $secure New value.
     * @return void
     */
    public static function setSecure(bool $secure): void
    {
        static::$secure = $secure;
    }

    /**
     * Sets transmit cookies over HTTP, disabling JavaScript access.
     *
     * @param bool $httpOnly New value.
     * @return void
     */
    public static function setHttpOnly(bool $httpOnly): void
    {
        static::$httpOnly = $httpOnly;
    }

    /**
     * Sets `SameSite` attribute.
     *
     * @param string $sameSite New value.
     * @return void
     */
    public static function setSameSite(string $sameSite): void
    {
        static::$sameSite = $sameSite;
    }
}
