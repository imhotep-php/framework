<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class FileRule extends AbstractRule
{
    use Traits\UtilsTrait;

    public function check(mixed $value): bool
    {
        if ( !($value = $this->makeUploadedFile($value)) )  {
            return false;
        }

        if (! $value->isValid()) {
            return false;
        }

        return true;
    }
}