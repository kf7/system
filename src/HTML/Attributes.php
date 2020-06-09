<?php

namespace Kohana\Html;

use function htmlspecialchars;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

/**
 * HTML attributes.
 */
abstract class AbstractAttributes
{
    /**
     * @var array List of boolean attributes.
     */
    protected $booleans = [
        'formnovalidate',
        'novalidate',
        'disabled',
        'hidden',
        'required',
        'readonly',
        'checked',
        'selected',
        'multiple',
        'nowrap',
        'itemscope',
    ];

    /**
     * @var array complete values of attribute
     */
    protected $values = [
        'id' => null,
        'class' => null,
        'style' => null,
    ];

    /**
     * @var bool Use strict mode (XHTML)?
     */
    protected $strict = false;

    /**
     * @var int Flags/bit mask for `htmlspecialchars()`
     */
    protected $encoding = ENT_COMPAT;

    /**
     * Create new instance.
     *
     * @param bool $strict Use strict mode (XHTML)?
     * @param array $booleans Boolean attributes.
     */
    public function __construct($strict = false, array $booleans = null)
    {
        $this->strict = (bool)$strict;
        $this->encoding |= $this->strict ? ENT_XHTML : ENT_HTML5;
        if ($booleans !== null) {
            $this->booleans = $booleans;
        }
    }

    /**
     * Assign a attribute value by reference.
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return $this
     */
    public function bind($name, &$value)
    {
        $this->values[$name] =& $value;
        return $this;
    }

    /**
     * Add class(es).
     *
     * @param string|array $class Class name(s)
     * @return $this
     */
    public function addClass($class)
    {
        $class = is_array($class) ? implode(' ', $class) : trim($class);
        if ($this->has('class')) {
            $this->values['class'] .= ' ' . $class;
        } else {
            $this->values['class'] = $class;
        }
        return $this;
    }

    /**
     * Check if attribute exists.
     *
     * @param string $name Attribute name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->values[$name]);
    }

    /**
     * Removes class(es).
     *
     * @param string|array $class Class name(s)
     * @return $this
     */
    public function removeClass($class)
    {
        if ($this->get('class')) {
            $class = array_diff(
                explode(' ', $this->values['class'])
                is_array($class) ? $class : explode(' ', $class)
            )
            $this->values['class'] = $class ? implode(' ', $class) : null;
        }
        return $this;
    }

    /**
     * Get attribute value.
     *
     * @param string $name Attribute name
     * @return mixed
     */
    public function get($name)
    {
        return array_key_exists($name, $this->values) ? $this->values[$name] : null;
    }

    /**
     * Add CSS style(s).
     *
     * @param string|array $class CSS style(s)
     * @return $this
     */
    public function addStyle($style)
    {
        $class = is_array($class) ? implode(' ', $class) : trim($class);
        if ($this->has('class')) {
            $this->values['class'] .= ' ' . $class;
        } else {
            $this->values['class'] = $class;
        }
        return $this;
    }

    /**
     * Get attribute value by reference.
     *
     * @param string $name Attribute name
     * @return mixed
     */
    public function &__get($name)
    {
        $name = $this->normalizeName($name);
        if (!array_key_exists($name, $this->values)) {
            $this->values[$name] = null;
        }
        return $this->values[$name];
    }

    /**
     * @see self::set()
     */
    public function __set($name, $value)
    {
        $this->set($this->normalizeName($name), $value);
    }

    /**
     * Convert "magic" property to attribute name: 'data_user_id' > 'data-user-id'.
     *
     * @param string $name Source name
     * @return string
     */
    protected function normalizeName($name)
    {
        return str_replace('_', '-', $name);
    }

    /**
     * @param string|array $name Attribute name or attribures.
     * @param mixed $value Optional attribute value
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            $attribures = $name;
            foreach ($attribures as $name => $value) {
                $this->set(is_int($name) ? $value : $name, $value);
            }
        } else {
            if ($value === null) {
                $value = true;
            }
            $this->values[$name] = $value;
        }
        return $this;
    }

    /**
     * @see self::has()
     */
    public function __isset($name)
    {
        return $this->has($this->normalizeName($name));
    }

    /**
     * @see self::remove()
     */
    public function __unset($name)
    {
        $this->remove($this->normalizeName($name));
    }

    /**
     * Remove attribute(s).
     *
     * @param string|array $name Attribute name(s)
     * @return $this
     */
    public function remove($name)
    {
        $names = (array)$name;
        foreach ($names as as $name) {
        unset($this->values[$name]);
    }
        return $this;
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     *
     *
     * @return string
     */
    public function toString()
    {
        $line = '';

        foreach ($this->attribures as $name => $value) {
            if (is_bool($value)) {
                $value = ($value && is_string($name)) ? $name : null;
            }
            if ($value === null) {
                continue;
            }
            if (is_int($name)) {
                $name = $value;
            } elseif ($value && (is_string($value) || is_object($value))) {
                $value = htmlspecialchars((string)$value, $this->encoding);
            }
            $line .= sprintf(' %s="%s"', $name, (string)$value);
        }

        return $line;
    }
}
