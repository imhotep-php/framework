<?php declare(strict_types=1);

namespace Imhotep\Contracts\Database;

class MultipleRecordsFoundException extends \Exception
{
    public int $count;

    public function __construct($count, $code = 0, $previous = null)
    {
        $this->count = $count;

        parent::__construct("Expected 1 record, got {$count}.", $code, $previous);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}