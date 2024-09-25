<?php declare(strict_types=1);

namespace Imhotep\Log\Handlers;

use Imhotep\Log\LogRecord;

class FileHandler extends AbstractHandler
{
    protected string $path;

    public function __construct(string $path, int|string $level)
    {
        parent::__construct($level);

        $this->path = $path;
    }

    public function handle(LogRecord $record): bool
    {
        return $this->write($this->getFormatter()->format($record));
    }

    protected function write(string $message): bool
    {
        return file_put_contents($this->path, $message, FILE_APPEND) !== false;
    }
}