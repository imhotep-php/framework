<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Validation\Rule;

class UploadedFile extends Rule
{
    use Traits\UtilsTrait;

    protected bool $implicit = true;

    protected string $message = 'The :attribute is not valid uploaded file.';

    protected ?float $minSize = null;

    protected ?float $maxSize = null;

    protected array $allowedImageTypes = ['gif','jpeg','jpg','png','bmp','tiff','ico','webp','avif'];

    public function setParameters(array $parameters): static
    {
        $this->parameters['min_size'] = array_shift($parameters);
        $this->parameters['max_size'] = array_shift($parameters);

        if (empty($parameters)) return $this;

        if ($this->getKey() !== 'image') {
            $this->parameters['allowed_types'] = $parameters;
            return $this;
        }

        if (count($parameters) > 0) {
            foreach ($parameters as $type) {
                if (in_array($type, $this->allowedImageTypes)) {
                    $this->parameters['allowed_types'][] = $type;
                }
            }
        }
        else {
            $this->parameters['allowed_types'] = $this->allowedImageTypes;
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        if ( !($value = $this->makeUploadedFile($value)) )  {
            return false;
        }

        if ($this->attribute && $this->attribute->hasRules('image')) {
            if (! $value->isValidImage()) {
                return false;
            }
        }
        elseif (! $value->isValid()) {
            return false;
        }

        $minSize = $this->getBytesSize($this->parameter('min_size'));
        if ($minSize && $value->getSize() < $minSize) {
            $this->setMessage('The :attribute file is too small, minimum size is :min_size bytes');
            return false;
        }

        $maxSize = $this->getBytesSize($this->parameter('max_size'));
        if ($maxSize && $value->getSize() > $maxSize) {
            $this->setMessage('The :attribute file is too large, maximum size is :max_size bytes');
            return false;
        }

        if ($allowedTypes = $this->parameter('allowed_types')) {
            $allowedTypes = is_array($allowedTypes) ? $allowedTypes : [$allowedTypes];

            if (! in_array($value->extension(), $allowedTypes)) {
                $this->setMessage('The :attribute file type must be :allowed_types');
                return false;
            }
        }

        return true;
    }
}