<?php

namespace Kohana\Log;

use Kohana\Exception;
use Kohana\Text;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function array_search;
use function array_slice;
use function in_array;
use function is_array;
use function range;
use function register_shutdown_function;
use function time;

use const LOG_ALERT;
use const LOG_CRIT;
use const LOG_DEBUG;
use const LOG_EMERG;
use const LOG_ERR;
use const LOG_INFO;
use const LOG_NOTICE;
use const LOG_WARNING;

/**
 * Message logging with observer-based log writing (loggers).
 */
abstract class AbstractLog implements LoggerInterface
{
    /**
     * @var array Priority levels of logging: `LOG_EMERG`(0) - `LOG_DEBUG`(7).
     */
    public const LEVELS = [
        LOG_EMERG => LogLevel::EMERGENCY,
        LOG_ALERT => LogLevel::ALERT,
        LOG_CRIT => LogLevel::CRITICAL,
        LOG_ERR => LogLevel::ERROR,
        LOG_WARNING => LogLevel::WARNING,
        LOG_NOTICE => LogLevel::NOTICE,
        LOG_INFO => LogLevel::INFO,
        LOG_DEBUG => LogLevel::DEBUG,
    ];

    /**
     * @var bool Immediately write to logs messages at shutdown.
     */
    protected $writeOnShutdown = false;

    /**
     * @var array List of added messages.
     */
    protected $messages = [];

    /**
     * @var WriterInterface[] List of log writers.
     */
    protected $writers = [];

    /**
     * Enable writing at shutdown.
     *
     * @param bool $writeOnShutdown Write the logs at shutdown.
     */
    public function __construct(bool $writeOnShutdown = false)
    {
        $this->writeOnShutdown = $writeOnShutdown;
        if ($this->writeOnShutdown) {
            register_shutdown_function([$this, 'write']);
        }
    }

    /**
     * Attaches a log writer, and optionally limits the levels of messages that will be written by the writer.
     *
     * @param LoggerInterface $writer instance
     * @param array|int $levels array of log levels to write OR max level to write
     * @param int $minLevel min level to write
     * @return $this
     */
    public function attachWriter(Writer $writer, $levels = [], $minLevel = 0)
    {
        if (!is_array($levels)) {
            $levels = range($minLevel, $levels);
        }
        $this->writers[] = [
            'object' => $writer,
            'levels' => $levels,
        ];

        return $this;
    }

    /**
     * Detaches a log writer. The same writer object must be used.
     *
     * @param LoggerInterface $writer instance
     * @return $this
     */
    public function detachWriter(LoggerInterface $writer)
    {
        $key = array_search($writer, $this->writers, true);
        if ($key !== false) {
            // Remove the writer
            unset($this->writers[$key]);
        }

        return $this;
    }

    /**
     * Adds a message to the log.
     *
     * @param int|string $level message level
     * @param object|string $message message text
     * @param array $context message values
     * @return $this
     * @uses Text::interpolate() interpolates context values into the message placeholders.
     */
    public function add($level, $message, array $context = [])
    {
        if ($context) {
            // Insert the values into the message
            $message = Text::interpolate($message, $context);
        }
        // grab a copy of the trace
        if (isset($context['exception']) && $context['exception'] instanceof Exception) {
            $trace = $context['exception']->getTrace();
        } else {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1);
        }
        // create a new message
        $this->messages[] = [
            'time' => time(),
            'level' => $level,
            'body' => $message,
            'trace' => $trace,
            'file' => $trace[0]['file'] ?? null,
            'line' => $trace[0]['line'] ?? null,
            'class' => $trace[0]['class'] ?? null,
            'function' => $trace[0]['function'] ?? null,
        ];
        if ($this->writeAtShutdown) {
            // Write logs as they are added
            $this->write();
        }

        return $this;
    }

    /**
     * Write and clear all of the messages.
     */
    public function write()
    {
        if ($this->messages) {
            // Import all messages locally
            $messages = $this->messages;
            // Reset the messages array
            $this->messages = [];
            foreach ($this->writers as $writer) {
                if (empty($writer['levels'])) {
                    // Write all of the messages
                    $writer['object']->write($messages);
                } else {
                    // Filtered messages
                    $filtered = [];
                    foreach ($messages as $message) {
                        if (in_array($message['level'], $writer['levels'], true)) {
                            // Writer accepts this kind of message
                            $filtered[] = $message;
                        }
                    }
                    // Write the filtered messages
                    $writer['object']->write($filtered);
                }
            }
        }
    }
}
