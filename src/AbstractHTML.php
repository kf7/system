<?php

namespace Kohana;

use function array_merge_recursive;
use function array_unique;
use function basename;
use function explode;
use function filter_var;
use function htmlentities;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

use const ENT_HTML401;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_XHTML;
use const FILTER_SANITIZE_EMAIL;

/**
 * HTML helper class. Provides generic methods for generating various HTML tags and making output HTML safe.
 */
abstract class AbstractHTML
{
    /**
     * @var string pseudo attribute of attribute pre-sets
     */
    public const ATTRIBUTE_PRESET = '@';

    /**
     * @var bool render as HTML5?
     */
    public static $html5 = true;

    /**
     * @var bool render as XHTML? (optional for HTML4)
     */
    public static $strict = true;

    /**
     * @var bool Automatically target external URLs to a new window?
     */
    public static $windowedUrls = true;

    /**
     * @var bool check wherever sort attributes
     */
    public static $attributeSortable = true;

    /**
     * @var array preferred order of attributes
     */
    public static $attributeOrder = [
        'action',
        'method',
        'type',
        'id',
        'name',
        'value',
        'href',
        'src',
        'width',
        'height',
        'cols',
        'rows',
        'size',
        'maxlength',
        'rel',
        'media',
        'accept-charset',
        'accept',
        'tabindex',
        'accesskey',
        'alt',
        'title',
        'class',
        'style',
        'selected',
        'checked',
        'readonly',
        'disabled',
    ];

    /**
     * @var array unescaped attributes
     */
    public static $attributeUnescaped = ['action', 'href', 'src'];

    /**
     * @var array single tags without inner content and closing tag
     */
    public static $singleTags = [
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    /**
     * @var array an array of preset name/attributes pairs, nested presets not supported
     */
    protected static $presetAttributes = [];

    /**
     * Returns bitmask of flags for functions `htmlentities()` and `htmlspecialchars()`.
     *
     * @return int
     */
    protected static function getEscapeFlags(): int
    {
        return ENT_QUOTES | (static::$html5 ? ENT_HTML5 : (static::$strict ? ENT_XHTML : ENT_HTML401));
    }

    /**
     * Convert special characters to HTML entities. All untrusted content should be passed through this method to
     * prevent XSS injections.
     *
     * @param object|string $value String to convert
     * @param bool $doubleEncode encode existing entities
     * @return string
     */
    public static function chars($value, bool $doubleEncode = true): string
    {
        return htmlspecialchars((string) $value, static::getEscapeFlags(), Kohana::$charset, $doubleEncode);
    }

    /**
     * Convert all applicable characters to HTML entities. All characters that cannot be represented in HTML with
     * the current character set will be converted to entities.
     *
     * @param object|string $value String to convert
     * @param bool $doubleEncode Encode existing entities
     * @return string
     */
    public static function entities($value, bool $doubleEncode = true): string
    {
        return htmlentities((string) $value, static::getEscapeFlags(), Kohana::$charset, $doubleEncode);
    }

    /**
     * Returns presets attributes.
     *
     * Note: `class` attributes are merge, other are replace.
     *
     * @param string $preset preset name or names separated by whitespace
     * @return array
     */
    public static function getPresetAttributes(string $preset): array
    {
        $attributes = [];
        $presets = explode(' ', $preset);
        foreach (array_unique($presets) as $preset) {
            if (empty(static::$presetAttributes[$preset])) {
                continue;
            }
            $preset = static::$presetAttributes[$preset];
            // split classes
            if (!empty($preset['class']) && is_string($preset['class'])) {
                $preset['class'] = explode(' ', $preset['class']);
            }
            $attributes = array_merge_recursive($attributes, $preset);
        }
        return $attributes;
    }

    /**
     * Preset attributes.
     *
     * @param string $preset preset name
     * @param array $attributes preset attributes
     * @return void
     */
    public static function setPresetAttributes(string $preset, array $attributes)
    {
        static::$presetAttributes[$preset] = $attributes;
    }

    /**
     * Compiles an array of Tag attributes into an attribute string. Optional, attributes can be sorted using
     * `self::$attributeOrder` for consistency.
     *
     * @param array $attributes attribute list
     * @return string
     */
    public static function attributes(array $attributes): string
    {
        if (!$attributes) {
            return '';
        }
        // replace sets to attributes
        if (isset($attributes[static::ATTRIBUTE_PRESET])) {
            // Attribute `class` stored tag classes as array
            if (isset($attributes['class']) && is_string($attributes['class'])) {
                $attributes['class'] = explode(' ', $attributes['class']);
            }
            $presetAttributes = static::getPresetAttributes($attributes[static::ATTRIBUTE_PRESET]);
            $attributes = array_merge_recursive($presetAttributes, $attributes);
            unset($attributes[static::ATTRIBUTE_PRESET]);
        }
        // normalize attributes before sorting
        foreach ($attributes as $key => $value) {
            if (is_array($value, [null, false, []], true)) {
                unset($attributes[$key]);
            } elseif (is_int($key)) {
                // assume non-associative keys are mirrored attributes
                unset($attributes[$key]);
                $key = $value;
                if (!static::$strict) {
                    // just use a key
                    $value = false;
                }
                // add correct attribute
                $attributes[$key] = $value;
            }
        }
        // sort attributes.
        if (static::$attributeSortable) {
            $sorted = [];
            foreach (static::$attributeOrder as $key) {
                if (isset($attributes[$key])) {
                    $sorted[$key] = $attributes[$key];
                }
            }
            $attributes = $sorted + $attributes;
        }
        // compiles attributes inline
        $compiled = '';
        foreach ($attributes as $key => $value) {
            $compiled .= ' ' . $key;
            if ($value !== false || static::$strict) {
                if (is_array($value)) {
                    $value = implode(' ', $value);
                } elseif (is_object($value)) {
                    $value = (string) $value;
                }
                if (is_string($value) && !in_array($key, static::$attributeUnescaped, true)) {
                    $value = static::chars($value);
                }
                $compiled .= '="' . $value . '"';
            }
        }
        return $compiled;
    }

    /**
     * Creates a HTML tag.
     *
     * @param string $name tag name
     * @param array $attributes tag attributes
     * @param object|string $body tag content
     * @return string
     */
    public static function tag(string $name, array $attributes = [], $body = ''): string
    {
        if (in_array($name, static::$singleTags)) {
            return sprintf('<%s%s%s>', $name, $attributes, static::$strict ? ' /' : '');
        }
        return sprintf('<%s%s>%s</%s>', $name, $attributes, (string) $body, $name);
    }

    /**
     * Create HTML link anchors. Note that the title is not escaped, to allow HTML elements within links (images, etc).
     *
     * @param object|string $uri URL or URI string
     * @param string $body inner content
     * @param array $attributes HTML anchor attributes
     * @return string
     */
    public static function anchor($uri, string $body = '', array $attributes = []): string
    {
        $url = (string) $uri;
        if (in_array($url, ['', '/'], true)) {
            // only use the base URL
            $uri = URL::base();
        } else {
            if (URL::isAbsolute($uri)) {
                if (static::$windowedUrls && !isset($attributes['target'])) {
                    // make the link open in a new window
                    $attributes['target'] = '_blank';
                }
            } elseif (!in_array($uri[0], ['#', '?'])) {
                // make the URI absolute for non-fragment and non-query anchors
                $uri = URL::site($uri);
            }
        }
        // Add the sanitized link to the attributes
        $attributes['href'] = $uri;
        return static::tag('a', $attributes, $body ?? basename($uri));
    }

    /**
     * Alias of `self::anchor()`.
     *
     * @param object|string $uri URL or URI string
     * @param string $body inner content
     * @param array $attributes HTML anchor attributes
     * @return string
     */
    public static function a($uri, string $body = '', array $attributes = []): string
    {
        return static::anchor($uri, $body, $attributes);
    }

    /**
     * Creates `mailto:` anchor tag.
     *
     * Note: that the title is not escaped, to allow HTML elements within links (images, etc).
     *
     * @param object|string $email email address to send to
     * @param string $title link text
     * @param array $attributes tag attributes
     * @return string
     */
    public static function mailto($email, string $title = '', array $attributes = []): string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $attributes['href'] = '&#109;&#097;&#105;&#108;&#116;&#111;&#058;' . $email;
        return static::tag($a, $attributes, $title ?: $email);
    }

    /**
     * Creates a style sheet link element.
     *
     * @param object|string $file file name
     * @param array $attributes Tag attributes
     * @return string
     */
    public static function style($file, array $attributes = []): string
    {
        $file = (string) $file;
        if (!URL::isAbsolute($file)) {
            // Add the base URL
            $file = URL::site($file);
        }
        // Set the stylesheet link
        $attributes['href'] = $file;
        // Set the stylesheet rel
        $attributes['rel'] = $attributes['rel'] ?? 'stylesheet';
        if (!static::$html5 && empty($attributes['type'])) {
            // Set the stylesheet type
            $attributes['type'] = 'text/css';
        }
        return static::tag('link', $attributes);
    }

    /**
     * Creates a inline style tag.
     *
     * @param object|string $body Inner style
     * @param array $attributes Tag attributes
     * @return string
     */
    public static function inlineStyle($body, array $attributes = []): string
    {
        if (!static::$html5 && empty($attributes['type'])) {
            // Set the stylesheet type
            $attributes['type'] = 'text/css';
        }
        unset($attributes['href'], $attributes['rel']);
        return static::tag('style', $attributes, (string) $body);
    }

    /**
     * Creates a script tag.
     *
     * @param object|string $file file URL
     * @param array $attributes Tag attributes
     * @return string
     */
    public static function script($file, array $attributes = []): string
    {
        $file = (string) $file;
        if (!URL::isAbsolute($file)) {
            // add the base URL
            $file = URL::site($file);
        }
        // set the script link
        $attributes['src'] = $file;
        if (!static::$html5 && empty($attributes['type'])) {
            // set the script type
            $attributes['type'] = 'text/javascript';
        }
        return static::tag('script', $attributes);
    }

    /**
     * Creates a inline script tag.
     *
     * @param object|string $body Inner code
     * @param array $attributes Tag attributes
     * @return void
     */
    public static function inlineScript($body, array $attributes = []): string
    {
        if (!static::$html5 && empty($attributes['type'])) {
            // Set the stylesheet type
            $attributes['type'] = 'text/javascript';
        }
        unset($attributes['src']);
        return static::tag('script', $attributes, (string) $body);
    }

    /**
     * Creates a image link.
     *
     * @param object|string $file URL to file
     * @param array $attributes Tag attributes
     * @return string
     */
    public static function image($file, array $attributes = []): string
    {
        $file = (string) $file;
        // add the image link
        $attributes['src'] = URL::isAbsolute($file) ? $file : URL::site($file);
        if (!isset($attributes['alt'])) {
            $attributes['alt'] = '';
        }
        return static::tag('img', $attributes);
    }

    /**
     * Alias `self::image()`.
     *
     * @param object|string $file URL to file
     * @param array $attributes Tag attributes
     * @return string
     */
    public static function img($file, array $attributes = []): string
    {
        return static::image($file, $attributes);
    }

    /**
     * Generates a meta tag.
     *
     * @param string $attribute attribute name (`name`, `http-equiv`, `charset`)
     * @param string $value attribute value
     * @param string $content value of `content` attribute
     * @return string
     */
    public static function meta(string $attribute, string $value, string $content = null): string
    {
        return static::tag('meta', [$attribute => $value, 'content' => static::chars($content)]);
    }
}
