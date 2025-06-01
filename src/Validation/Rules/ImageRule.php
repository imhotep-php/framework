<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class ImageRule extends FileRule
{
    protected array $defaultExtensions = ['gif','jpeg','jpg','png','bmp','tiff','ico','webp','avif'];

    public function setParameters(array $parameters): static
    {
        $this->parameters['extensions'] =
            count($parameters) > 0 ? $parameters : $this->defaultExtensions;

        return $this;
    }

    public function check(mixed $value): bool
    {
        if (! parent::check($value)) {
            return false;
        }

        if (! in_array($value->extension(), $this->parameter('extensions'))) {
            return false;
        }

        return true;
    }
}