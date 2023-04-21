<?php

declare(strict_types=1);

namespace Imhotep\Log\Formatter;

use Imhotep\Log\LogRecord;

class LineFormatter extends BaseFormatter
{
    public function format(LogRecord $record): string
    {
        $format = "[%s] %s.%s: %s %s %s";

        if (isset($record->context['exception']) && is_object($record->context['exception'])) {
            $record->context['exception'] = $this->normalizeException($record->context['exception']);
        }

        $format = sprintf(
            $format,
            $record->datetime,
            $record->channel,
            $record->levelName,
            $record->message,
            json_encode($record->context),
            json_encode($record->extra)
        );

        return $format.PHP_EOL;
    }
}