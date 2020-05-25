<?php
namespace Kohana7\Log;

use Kohana7\Text;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Message logging with observer-based log writing (loggers).
 */
abstract class AbstractLogger extends LogLevel implements LoggerInterface
{

    /*
     * public const EMERGENCY = LOG_EMERG; // 0
     * public const ALERT = LOG_ALERT; // 1
     * public const CRITICAL = LOG_CRIT; // 2
     * public const ERROR = LOG_ERR; // 3
     * public const WARNING = LOG_WARNING; // 4
     * public const NOTICE = LOG_NOTICE; // 5
     * public const INFO = LOG_INFO; // 6
     * public const DEBUG = LOG_DEBUG; // 7
     *
     * const EMERGENCY = 'emergency';
     * const ALERT = 'alert';
     * const CRITICAL = 'critical';
     * const ERROR = 'error';
     * const WARNING = 'warning';
     * const NOTICE = 'notice';
     * const INFO = 'info';
     * const DEBUG = 'debug';
     */

    /**
     *
     * @var array Priority levels of logging: LOG_EMERG(0) - LOG_DEBUG(7)
     */
    public const LEVELS = [
        \LOG_EMERG => static::EMERGENCY,
        \LOG_ALERT => static::ALERT,
        \LOG_CRIT => static::CRITICAL,
        \LOG_ERR => static::ERROR,
        \LOG_WARNING => static::WARNING,
        \LOG_NOTICE => static::NOTICE,
        \LOG_INFO => static::INFO,
        \LOG_DEBUG => static::DEBUG
    ];

    /**
     *
     * @var bool immediately write to logs messages at shutdown
     */
    protected $writeAtShutdown = false;

    /**
     *
     * @var array list of added messages
     */
    protected $messages = [];

    /**
     *
     * @var LoggerInterface[] list of log writers
     */
    protected $writers = [];

    /**
     * Enable writing at shutdown.
     *
     * @param bool $writeAtShutdown
     * @return void
     */
    public function __construct(bool $writeAtShutdown = false)
    {
        $this->writeAtShutdown = $writeAtShutdown;
        if ($this->writeAtShutdown) {
            // Write the logs at shutdown
            \register_shutdown_function([
                $this,
                'write'
            ]);
        }
    }

    /**
     * Attaches a log writer, and optionally limits the levels of messages that
     * will be written by the writer.
     *
     * @param LoggerInterface $writer
     *            instance
     * @param array|int $levels
     *            array of log levels to write OR max level to write
     * @param int $minLevel
     * @return $this
     */
    public function attachWriter(LoggerInterface $writer, $levels = [], $minLevel = 0)
    {
        if (! \is_array($levels)) {
            $levels = \range($minLevel, $levels);
        }
        $this->writers[] = [
            'object' => $writer,
            'levels' => $levels
        ];
        return $this;
    }

    /**
     * Detaches a log writer.
     * The same writer object must be used.
     *
     * @param LoggerInterface $writer
     *            instance
     * @return $this
     */
    public function detachWriter(LoggerInterface $writer)
    {
        $key = \array_search($writer, $this->writers);
        if ($key !== false) {
            // Remove the writer
            unset($this->writers[$key]);
        }
        return $this;
    }

    /**
     * Adds a message to the log.
     *
     * @param string|int $level
     *            message level
     * @param string|object $message
     *            message text
     * @param array $context
     *            message values
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
            $trace = \array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1);
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
            'function' => $trace[0]['function'] ?? null
        ];
        if ($this->writeAtShutdown) {
            // Write logs as they are added
            $this->write();
        }
        return $this;
    }

    /**
     * Write and clear all of the messages.
     *
     * @return void
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
                        if (\in_array($message['level'], $writer['levels'])) {
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
