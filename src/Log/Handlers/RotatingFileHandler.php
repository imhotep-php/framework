<?php

declare(strict_types=1);

namespace Imhotep\Log\Handlers;

use Imhotep\Log\LogRecord;

class RotatingFileHandler extends AbstractHandler
{
    protected string $path;

    protected int $maxFiles;

    protected string $dateFormat = "Y-m-d";

    public function __construct(string $path, int $maxFiles, int|string $level)
    {
        parent::__construct($level);

        $this->path = $path;
        $this->maxFiles = $maxFiles;
    }

    public function handle(LogRecord $record): bool
    {
        return $this->write($this->getFormatter()->format($record));
    }

    protected function write(string $message): bool
    {
        return file_put_contents($this->getTimedPath(), $message, FILE_APPEND) !== false;
    }

    protected function getTimedPath(): string
    {
        $pathInfo = pathinfo($this->path);

        $timedPath = $pathInfo['filename'].'-'.date($this->dateFormat);

        if (isset($pathInfo['extension'])) {
            $timedPath .= '.'.$pathInfo['extension'];
        }

        return $pathInfo['dirname'].'/'.$timedPath;
    }
}