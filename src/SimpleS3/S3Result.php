<?php

declare(strict_types=1);

namespace Imhotep\SimpleS3;

/**
 *
 */
class S3Result
{
    public int $statusCode = 0;

    public function __construct(
        protected array $meta,
        protected mixed $data,
        protected array $error
    ) {
        $this->statusCode = $this->meta['statusCode'];
    }

    public function get(string $name = null): mixed
    {
        if (! empty($name) && is_array($this->data)) {
            if (isset($this->data[$name])) {
                return $this->data[$name];
            }

            return null;
        }

        return $this->data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMeta(string $name = null)
    {
        if (! empty($name)) {
            if (isset($this->meta[$name])) {
                return $this->meta[$name];
            }
            if (isset($this->meta['headers'][$name])) {
                return $this->meta['headers'][$name];
            }

            return null;
        }

        return $this->meta;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            '@metadata' => $this->meta
        ];
    }
}