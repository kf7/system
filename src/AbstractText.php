<?php

namespace Kohana;

use function array_pop;
use function count;
use function implode;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function rtrim;
use function sprintf;
use function strlen;
use function strtr;
use function trim;

/**
 * Text helper class. Provides simple methods for working with text.
 */
abstract class AbstractText
{
    /**
     * @var string pattern of message placeholder
     */
    public const PLACEHOLDER = '{%s}';

    /**
     * @var array number units and text equivalents
     */
    public static $units = [
        1000000000 => 'billion',
        1000000 => 'million',
        1000 => 'thousand',
        100 => 'hundred',
        90 => 'ninety',
        80 => 'eighty',
        70 => 'seventy',
        60 => 'sixty',
        50 => 'fifty',
        40 => 'fourty',
        30 => 'thirty',
        20 => 'twenty',
        19 => 'nineteen',
        18 => 'eighteen',
        17 => 'seventeen',
        16 => 'sixteen',
        15 => 'fifteen',
        14 => 'fourteen',
        13 => 'thirteen',
        12 => 'twelve',
        11 => 'eleven',
        10 => 'ten',
        9 => 'nine',
        8 => 'eight',
        7 => 'seven',
        6 => 'six',
        5 => 'five',
        4 => 'four',
        3 => 'three',
        2 => 'two',
        1 => 'one',
    ];

    /**
     * Limits a phrase to a given number of words.
     *
     * @param string $str phrase to limit words of
     * @param int $limit number of words to limit to
     * @param string $endChar end character or entity
     * @return string
     */
    public static function limitWords($str, int $limit = 100, string $endChar = '…'): string
    {
        if (trim($str) === '') {
            return $str;
        }
        if ($limit < 1) {
            return $endChar;
        }
        // Only attach the end character if the matched string is shorter than the starting string.
        if (!preg_match('/^\s*+(?:\S++\s*+){1,' . $limit . '}/u', $str, $matches)) {
            return $endChar;
        }
        return rtrim($matches[0]) . (strlen($matches[0]) === strlen($str) ? '' : $endChar);
    }

    /**
     * Limits a phrase to a given number of characters.
     *
     * @param   string  $str            phrase to limit characters of
     * @param   int $limit          number of characters to limit to
     * @param   string  $endChar       end character or entity
     * @param   bool $preserveWords enable or disable the preservation of words while limiting
     * @return  string
     */
    public static function limitChars(
        $str,
        int $limit = 100,
        string $endChar = '…',
        bool $preserveWords = false
    ): string {
        if (trim($str) === '' || mb_strlen($str, 'UTF-8') <= $limit) {
            return $str;
        }

        if ($limit <= 0) {
            return $endChar;
        }

        if (! $preserveWords) {
            return rtrim(mb_substr($str, 0, $limit, 'UTF-8')) . $endChar;
        }

        // Don't preserve words. The limit is considered the top limit.
        // No strings with a length longer than $limit should be returned.
        if (!preg_match('/^.{0,' . $limit . '}\s/us', $str, $matches)) {
            return $endChar;
        }

        return rtrim($matches[0]) . (strlen($matches[0]) === strlen($str) ? '' : $endChar);
    }

    /**
     * Alternates between two or more strings.
     *
     * Note that using multiple iterations of different strings may produce
     * unexpected results.
     *
     * @param   string  $str,... strings to alternate between
     * @return  string
     */
    public static function alternate()
    {
        static $i;

        if (func_num_args() === 0) {
            $i = 0;
            return '';
        }

        $args = func_get_args();
        return $args[($i++ % count($args))];
    }

    /**
     * Generates a random string of a given type and length.
     *     $str = Text::random(); // 8 character random string
     * The following types are supported:
     * alnum
     * :  Upper and lower case a-z, 0-9 (default)
     * alpha
     * :  Upper and lower case a-z
     * hexdec
     * :  Hexadecimal characters a-f, 0-9
     * distinct
     * :  Uppercase characters and numbers that cannot be confused
     * You can also create a custom type by providing the "pool" of characters
     * as the type.
     *
     * @param string $type a type of pool, or a string of characters to use as the pool
     * @param int $length length of string to return
     * @return  string
     * @uses    UTF8::split
     */
    public static function random($type = null, $length = 8)
    {
        if ($type === null) {
            // Default is to generate an alphanumeric string
            $type = 'alnum';
        }

        $utf8 = false;

        switch ($type) {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'hexdec':
                $pool = '0123456789abcdef';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                break;
            default:
                $pool = (string)$type;
                $utf8 = !UTF8::is_ascii($pool);
                break;
        }

        // Split the pool into an array of characters
        $pool = ($utf8 === true) ? UTF8::str_split($pool, 1) : str_split($pool, 1);

        // Largest pool key
        $max = count($pool) - 1;

        $str = '';
        for ($i = 0; $i < $length; $i++) {
            // Select a random character from the pool and add it to the string
            $str .= $pool[mt_rand(0, $max)];
        }

        // Make sure alnum strings contain at least one letter and one digit
        if ($type === 'alnum' and $length > 1) {
            if (ctype_alpha($str)) {
                // Add a random digit
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
            } elseif (ctype_digit($str)) {
                // Add a random letter
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
            }
        }

        return $str;
    }

    /**
     * Uppercase words that are not separated by spaces, using a custom
     * delimiter or the default.
     *
     *      $str = Text::ucfirst('content-type'); // returns "Content-Type"
     *
     * @param   string  $string     string to transform
     * @param   string  $delimiter  delimiter to use
     * @uses    UTF8::ucfirst
     * @return  string
     */
    public static function ucfirst($string, $delimiter = '-')
    {
        // Put the keys back the Case-Convention expected
        return implode($delimiter, array_map('UTF8::ucfirst', explode($delimiter, $string)));
    }

    /**
     * Reduces multiple slashes in a string to single slashes.
     *
     *     $str = Text::reduce_slashes('foo//bar/baz'); // "foo/bar/baz"
     *
     * @param   string  $str    string to reduce slashes of
     * @return  string
     */
    public static function reduce_slashes($str)
    {
        return preg_replace('#(?<!:)//+#', '/', $str);
    }

    /**
     * Replaces the given words with a string.
     *     // Displays "What the #####, man!"
     *     echo Text::censor('What the frick, man!', array(
     *         'frick' => '#####',
     *     ));
     *
     * @param string $str phrase to replace words in
     * @param array $badwords words to replace
     * @param string $replacement replacement string
     * @param bool $replace_partial_words replace words across word boundaries (space, period, etc)
     * @return  string
     * @uses    UTF8::strlen
     */
    public static function censor($str, $badwords, $replacement = '#', $replace_partial_words = true)
    {
        foreach ((array) $badwords as $key => $badword) {
            $badwords[$key] = str_replace('\*', '\S*?', preg_quote((string) $badword));
        }

        $regex = '(' . implode('|', $badwords) . ')';

        if ($replace_partial_words === false) {
            // Just using \b isn't sufficient when we need to replace a badword that already contains word boundaries itself
            $regex = '(?<=\b|\s|^)' . $regex . '(?=\b|\s|$)';
        }

        $regex = '!' . $regex . '!ui';

        // if $replacement is a single character: replace each of the characters of the badword with $replacement
        if (UTF8::strlen($replacement) == 1) {
            return preg_replace_callback($regex, function ($matches) use ($replacement) {
                return str_repeat($replacement, UTF8::strlen($matches[1]));
            }, $str);
        }

        // if $replacement is not a single character, fully replace the badword with $replacement
        return preg_replace($regex, $replacement, $str);
    }

    /**
     * Finds the text that is similar between a set of words.
     *
     *     $match = Text::similar(array('fred', 'fran', 'free'); // "fr"
     *
     * @param   array   $words  words to find similar text of
     * @return  string
     */
    public static function similar(array $words)
    {
        // First word is the word to match against
        $word = current($words);

        for ($i = 0, $max = strlen($word); $i < $max; ++$i) {
            foreach ($words as $w) {
                // Once a difference is found, break out of the loops
                if (!isset($w[$i]) or $w[$i] !== $word[$i]) {
                    break 2;
                }
            }
        }

        // Return the similar text
        return substr($word, 0, $i);
    }

    /**
     * Converts text email addresses and anchors into links. Existing links
     * will not be altered.
     *
     *     echo Text::auto_link($text);
     *
     * [!!] This method is not foolproof since it uses regex to parse HTML.
     *
     * @param   string  $text   text to auto link
     * @return  string
     * @uses    Text::auto_link_urls
     * @uses    Text::auto_link_emails
     */
    public static function auto_link($text)
    {
        // Auto link emails first to prevent problems with "www.domain.com@example.com"
        return Text::auto_link_urls(Text::auto_link_emails($text));
    }

    /**
     * Converts text anchors into links. Existing links will not be altered.
     *
     *     echo Text::auto_link_urls($text);
     *
     * [!!] This method is not foolproof since it uses regex to parse HTML.
     *
     * @param   string  $text   text to auto link
     * @return  string
     * @uses    HTML::anchor
     */
    public static function auto_link_urls($text)
    {
        // Find and replace all http/https/ftp/ftps links that are not part of an existing html anchor
        $text = preg_replace_callback('~\b(?<!href="|">)(?:ht|f)tps?://[^<\s]+(?:/|\b)~i', 'Text::_auto_link_urls_callback1', $text);

        // Find and replace all naked www.links.com (without http://)
        return preg_replace_callback('~\b(?<!://|">)www(?:\.[a-z0-9][-a-z0-9]*+)+\.[a-z]{2,6}[^<\s]*\b~i', 'Text::_auto_link_urls_callback2', $text);
    }

    protected static function _auto_link_urls_callback1($matches)
    {
        return HTML::anchor($matches[0]);
    }

    protected static function _auto_link_urls_callback2($matches)
    {
        return HTML::anchor('http://' . $matches[0], $matches[0]);
    }

    /**
     * Converts text email addresses into links. Existing links will not
     * be altered.
     *
     *     echo Text::auto_link_emails($text);
     *
     * [!!] This method is not foolproof since it uses regex to parse HTML.
     *
     * @param   string  $text   text to auto link
     * @return  string
     * @uses    HTML::mailto
     */
    public static function auto_link_emails($text)
    {
        // Find and replace all email addresses that are not part of an existing html mailto anchor
        // Note: The "58;" negative lookbehind prevents matching of existing encoded html mailto anchors
        //       The html entity for a colon (:) is &#58; or &#058; or &#0058; etc.
        return preg_replace_callback(
            '~\b(?<!href="mailto:|58;)(?!\.)[-+_a-z0-9.]++(?<!\.)@(?![-.])[-a-z0-9.]+(?<!\.)\.[a-z]{2,6}\b(?!</a>)~i',
            'Text::_auto_link_emails_callback',
            $text
        );
    }

    protected static function _auto_link_emails_callback($matches)
    {
        return HTML::mailto($matches[0]);
    }

    /**
     * Automatically applies "p" and "br" markup to text.
     * Basically [nl2br](http://php.net/nl2br) on steroids.
     *     echo Text::auto_p($text);
     * [!!] This method is not foolproof since it uses regex to parse HTML.
     *
     * @param string $str subject
     * @param bool $br convert single linebreaks to <br />
     * @return  string
     */
    public static function auto_p($str, $br = true)
    {
        // Trim whitespace
        if (($str = trim($str)) === '') {
            return '';
        }

        // Standardize newlines
        $str = str_replace(["\r\n", "\r"], "\n", $str);

        // Trim whitespace on each line
        $str = preg_replace('~^[ \t]+~m', '', $str);
        $str = preg_replace('~[ \t]+$~m', '', $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found = (strpos($str, '<') !== false)) {
            // Elements that should not be surrounded by p tags
            $no_p = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th))';

            // Put at least two linebreaks before and after $no_p elements
            $str = preg_replace('~^<' . $no_p . '[^>]*+>~im', "\n$0", $str);
            $str = preg_replace('~</' . $no_p . '\s*+>$~im', "$0\n", $str);
        }

        // Do the <p> magic!
        $str = '<p>' . trim($str) . '</p>';
        $str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found !== false) {
            // Remove p tags around $no_p elements
            $str = preg_replace('~<p>(?=</?' . $no_p . '[^>]*+>)~i', '', $str);
            $str = preg_replace('~(</?' . $no_p . '[^>]*+>)</p>~i', '$1', $str);
        }

        // Convert single linebreaks to <br />
        if ($br === true) {
            $str = preg_replace('~(?<!\n)\n(?!\n)~', "<br />\n", $str);
        }

        return $str;
    }

    /**
     * Returns human readable sizes. Based on original functions written by
     * [Aidan Lister](http://aidanlister.com/repos/v/function.size_readable.php)
     * and [Quentin Zervaas](http://www.phpriot.com/d/code/strings/filesize-format/).
     *
     *     echo Text::bytes(filesize($file));
     *
     * @param int $bytes size in bytes
     * @param string $force_unit a definitive unit
     * @param string $format the return string format
     * @param bool $si whether to use SI prefixes or IEC
     * @return  string
     */
    public static function bytes($bytes, $force_unit = null, $format = null, $si = true)
    {
        // Format string
        $format = ($format === null) ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary)
        if ($si == false or strpos($force_unit, 'i') !== false) {
            $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
            $mod = 1024;
        } // SI prefixes (decimal)
        else {
            $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
            $mod = 1000;
        }

        // Determine unit to use
        if (($power = array_search((string) $force_unit, $units)) === false) {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / ($mod ** $power), $units[$power]);
    }

    /**
     * Format a number to human-readable text.
     *
     * @param  int|string $number number to format
     * @return string
     */
    public static function number($number): string
    {
        // The number must always be an integer
        $number = (int) $number;

        // Uncompiled text version
        $text = [];

        // Last matched unit within the loop
        $last_unit = null;

        // The last matched item within the loop
        $last_item = '';

        foreach (static::$units as $unit => $name) {
            if ($number / $unit >= 1) {
                // $value = the number of times the number is divisible by unit
                $number -= $unit * ($value = (int) floor($number / $unit));
                // Temporary var for textifying the current unit
                $item = '';

                if ($unit < 100) {
                    if ($last_unit < 100 && $last_unit >= 20) {
                        $last_item .= '-' . $name;
                    } else {
                        $item = $name;
                    }
                } else {
                    $item = static::number($value) . ' ' . $name;
                }

                // In the situation that we need to make a composite number then we need to modify the previous entry.
                if (empty($item)) {
                    array_pop($text);

                    $item = $last_item;
                }

                $last_item = $text[] = $item;
                $last_unit = $unit;
            }
        }

        if (count($text) > 1) {
            $and = array_pop($text);
        }

        $text = implode(', ', $text);

        if (isset($and)) {
            $text .= ' & ' . $and;
        }

        return $text;
    }

    /**
     * Prevents widow words by inserting a non-breaking space between the last two words.
     *
     * @param string $str text to remove widows from
     * @return string
     */
    public static function widont(string $str): string
    {
        // use '%' as delimiter and 'x' as modifier
        $pattern = '%'
            // must be proceeded by an approved inline opening or closing tag or a nontag/nonspace
            . '((?:</?(?:a|em|span|strong|i|b)[^>]*>)|[^<>\s])'
            // the space to replace
            . '\s+'
            // must be flollowed by non-tag non-space characters
            . '([^<>\s]+'
            // optional white space
            . '\s*'
            // optional closing inline tags with optional white space after each
            . '(</(a|em|span|strong|i|b)>\s*)*'
            // end with a closing p, h1-6, li or the end of the string
            . '((</(p|h[1-6]|li|dt|dd)>)|$))'
            . '%x';
        return preg_replace($pattern, '$1&nbsp;$2', $str);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message
     *
     * @param object|string $message a message with brace-delimited placeholder names
     * @param array $context a context array of placeholder names => replacement values
     * @return string
     */
    public static function interpolate($message, array $context): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $value) {
            $key = sprintf(static::PLACEHOLDER, $key);
            // quoted string more readable
            $replace[$key] = (is_string($value) && preg_match('/\s/', $value)) ? '"' . $value . '"' : $value;
        }
        // interpolate replacement values into the message and return
        return strtr((string) $message, $replace);
    }
}
