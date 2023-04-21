<?php

namespace Imhotep\Contracts\Log;

use Imhotep\Log\LogRecord;

interface Formatter
{
    public function format(LogRecord $record): string;

    public function formatBatch(array $records): array;
}