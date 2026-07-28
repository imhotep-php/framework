<?php declare(strict_types=1);

namespace Imhotep\Http;

use InvalidArgumentException;

class FileBag extends ParameterBag
{
    protected const FILE_KEYS = ['error', 'full_path', 'name', 'size', 'tmp_name', 'type'];

    public function __construct(array $files = [])
    {
        parent::__construct();

        foreach ($files as $key => $file) {
            $this->set($key, $file);
        }
    }

    public function set(string $key, mixed $value): static
    {
        if ($value instanceof UploadedFile) {
            parent::set($key, $value);
        }
        elseif (is_array($value)) {
            parent::set($key, $this->convert($value));
        }
        else {
            throw new InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile.');
        }

        return $this;
    }

    public function add(array $files = []): static
    {
        foreach ($files as $key => $file) {
            $this->set($key, $file);
        }

        return $this;
    }

    protected function convert(array $files): array|UploadedFile
    {
        if (isset($files['tmp_name']) && is_string($files['tmp_name'])) {
            return UploadedFile::createFrom($files);
        }

        return array_map(function ($v) {
            return UploadedFile::createFrom($v);
        }, $this->fixFilesArray($files));
    }

    protected function fixFilesArray(array $files): array
    {
        $result = [];

        if (isset($files['name']) && is_array($files['name'])) {
            foreach ($files['name'] as $key => $name) {
                $result[] = [
                    'error' => $files['error'][$key],
                    'name' => $name,
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'size' => $files['size'][$key],
                ];
            }
        }

        return $result;
    }
}