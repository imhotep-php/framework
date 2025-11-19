<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Http\UploadedFile;

class MimeRule extends AbstractRule
{
    protected array $mimes = [];

    public function setParameters(array $parameters): static
    {
        $this->mimes = array_map('strtolower', $parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        if (empty($this->mimes)) {
            return true;
        }

        if ($value instanceof UploadedFile) {
            return in_array($value->originalExtension(), $this->mimes);
        }

        return false;
    }
}