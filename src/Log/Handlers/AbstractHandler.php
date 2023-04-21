<?php

declare(strict_types=1);

namespace Imhotep\Log\Handlers;

use Imhotep\Contracts\Log\Formatter;
use Imhotep\Contracts\Log\Handler;
use Imhotep\Log\Formatter\LineFormatter;
use Imhotep\Log\Logger;
use Imhotep\Log\LogRecord;

abstract class AbstractHandler implements Handler
{
    protected int $level;

    public function __construct(int|string $level)
    {
        $this->level = Logger::toLevel($level);
    }

    public function isHandling(int $level): bool
    {
        return $level >= $this->level;
    }

    abstract public function handle(LogRecord $record): bool;



    protected Formatter $formatter;

    public function setFormatter(Formatter $formatter): void
    {
        $this->formatter = $formatter;
    }

    public function getFormatter(): Formatter
    {
        if (! isset($this->formatter)) {
            $this->formatter = $this->getDefaultFormatter();
        }

        return $this->formatter;
    }

    public function getDefaultFormatter(): Formatter
    {
        return new LineFormatter();
    }
}