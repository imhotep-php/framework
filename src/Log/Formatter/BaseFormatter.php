<?php

declare(strict_types=1);

namespace Imhotep\Log\Formatter;

use Imhotep\Contracts\Log\Formatter;
use Imhotep\Log\LogRecord;

abstract class BaseFormatter implements Formatter
{
    abstract public function format(LogRecord $record): string;

    public function formatBatch(array $records): array
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

    protected function normalizeException(\Throwable $e): array
    {
        $result = [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'code' => $e->getCode(),
        ];

        return $result;
    }
}