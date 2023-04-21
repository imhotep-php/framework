<?php

namespace Imhotep\Log;

use Imhotep\Contracts\Log\Handler;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    public const DEBUG = 100;

    public const INFO = 200;

    public const NOTICE = 250;

    public const WARNING = 300;

    public const ERROR = 400;

    public const CRITICAL = 500;

    public const ALERT = 550;

    public const EMERGENCY = 600;

    public const LEVELS = [
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    protected const RFC_5424_LEVELS = [
        7 => self::DEBUG,
        6 => self::INFO,
        5 => self::NOTICE,
        4 => self::WARNING,
        3 => self::ERROR,
        2 => self::CRITICAL,
        1 => self::ALERT,
        0 => self::EMERGENCY,
    ];

    protected string $name = '';

    protected array $handlers = [];

    protected array $context = [];

    public function __construct(string $name = '', array $handlers = [])
    {
        $this->name = $name;

        $this->setHandlers($handlers);
    }

    public function pushHandler(Handler $handler): static
    {
        array_unshift($this->handlers, $handler);

        return $this;
    }

    public function popHandler(): Handler
    {
        if (empty($this->handlers)) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }

        return array_shift($this->handlers);
    }

    public function setHandlers(array $handlers): static
    {
        $this->handlers = $handlers;

        return $this;
    }

    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public static function toLevel($level): int
    {
        if (is_int($level)) {
            if (isset(static::RFC_5424_LEVELS[$level])) {
                $level = static::RFC_5424_LEVELS[$level];
            }

            if (isset(static::LEVELS[$level])) {
                return $level;
            }
        }

        if (is_string($level)) {
            $upper = strtr($level, 'abcdefgilmnortuwy', 'ABCDEFGILMNORTUWY');

            if (defined(__CLASS__.'::'.$upper)) {
                return constant(__CLASS__ . '::' . $upper);
            }
        }

        throw new \InvalidArgumentException('Level "'.$level.'" is not defined, use one of: '.implode(', ', array_keys(static::LEVELS) + static::LEVELS));
    }

    public function withContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function withoutContext(): static
    {
        $this->context = [];

        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
        $level = static::toLevel($level);

        $record = null;

        foreach ($this->handlers as $handler) {
            if (is_null($record)) {
                if (! $handler->isHandling($level)) {
                    continue;
                }

                $record = new LogRecord($message, $context, date("Y-m-d H:i:s"), $level, $this->name);
            }

            try {
                $handler->handle($record);
            }
            catch (\Throwable $e) {
                // ...
            }
        }
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }
}