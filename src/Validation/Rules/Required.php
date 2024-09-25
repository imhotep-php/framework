<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Validation\Rule;

class Required extends Rule
{
    use Traits\UtilsTrait;

    protected bool $implicit = true;

    protected string $message = 'The :attribute is required';

    public function check(mixed $value): bool
    {
        if ($this->attribute && $this->attribute->hasRules('file')) {
            return $this->isUploadedFileValue($value);
        }

        if (is_string($value)) {
            return mb_strlen(trim($value), 'UTF-8') > 0;
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return ! is_null($value);
    }
}