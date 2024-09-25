<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules\Traits;

use Imhotep\Contracts\Validation\ValidationException;
use Imhotep\Http\UploadedFile;

trait UtilsTrait
{
    public function isUploadedFile(mixed $value): bool
    {
        $value = $this->makeUploadedFile($value);

        if (is_null($value)) {
            return false;
        }

        return $value->isUploaded();
    }

    public function isUploadedFileValue(mixed $value): bool
    {
        if ($value instanceof UploadedFile) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        foreach (['name', 'type', 'tmp_name', 'size', 'error'] as $key) {
            if (! array_key_exists($key, $value)) return false;
        }

        return true;
    }

    public function makeUploadedFile(mixed $value): ?UploadedFile
    {
        if ($value instanceof UploadedFile) {
            return $value;
        }

        if (is_array($value) && is_string($value['tmp_name'])) {
            return UploadedFile::createFrom($value);
        }

        return null;
    }

    public function getBytesSize(mixed $size): ?float
    {
        if (is_numeric($size)) {
            return (float)$size;
        }

        if (! is_string($size)) {
            return null;
        }

        if (! preg_match("/^(?<number>((\d+)?\.)?\d+)(?<suffix>(B|K|M|G|T|P)B?)?$/i", $size, $match)) {
            throw new ValidationException("Size [$size] is not valid format");
        }

        $number = (float)$match['number'];
        $suffix = strtoupper($match['suffix'] ?? '');

        $exponent = match($suffix) {
            'K', 'KB' => 1,
            'M', 'MB' => 2,
            'G', 'GB' => 3,
            'T', 'TB' => 4,
            'P', 'PB' => 5,
            default => 0,
        };

        return $number * (1024 ** $exponent);
    }

    public function getValueSize(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        elseif (is_string($value)) {
            return (float) mb_strlen($value, 'UTF-8');
        }
        elseif (is_array($value)) {
            return (float) count($value);
        }
        elseif ($file = $this->makeUploadedFile($value)) {
            return (float) $file->getSize();
        }

        return null;
    }
}