<?php

declare(strict_types=1);

namespace Imhotep\Log;

class LogRecord
{
    public string $levelName;

    public function __construct(
        public string $message,
        public array $context,
        public string $datetime,
        public int $level = 100,
        public string $channel = '',
        public array $extra = []
    )
    {
        $this->levelName = Logger::LEVELS[$this->level];
    }
}