<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\IModifyValue;
use DateTime;
use DateTimeInterface;
use Exception;

class DateRule extends AbstractRule implements IModifyValue
{
    public function check(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }

    public function modifyValue(mixed $value): mixed
    {
        if ($date = $this->makeDateTime($value)) {
            return $date;
        }

        return $value;
    }

    protected function makeDatetime(mixed $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_numeric($value)) {
            if (! is_int($value) && !ctype_digit((string)$value)) {
                return null;
            }

            try {
                return (new DateTime())->setTimestamp((int)$value);
            } catch (Exception) {
                return null;
            }
        }

        if (is_string($value) && ($timestamp = strtotime($value))) {
            return (new DateTime())->setTimestamp($timestamp);
        }

        return null;
    }
}