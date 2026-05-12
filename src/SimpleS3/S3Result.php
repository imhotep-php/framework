<?php declare(strict_types=1);

namespace Imhotep\SimpleS3;

use ArrayAccess;

/**
 *
 */
class S3Result implements ArrayAccess
{
    public int $statusCode = 0;

    public function __construct(
        protected array $meta,
        protected mixed $data,
        protected array $error
    ) {
        $this->statusCode = $this->meta['statusCode'];
    }

    public function get(?string $name = null, mixed $default = null): mixed
    {
        if (! empty($name) && is_array($this->data)) {
            if (isset($this->data[$name])) {
                return $this->data[$name];
            }

            return $default;
        }

        return $this->data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function getMeta(?string $name = null, mixed $default = null)
    {
        if (! empty($name)) {
            if (isset($this->meta[$name])) {
                return $this->meta[$name];
            }
            if (isset($this->meta['headers'][$name])) {
                return $this->meta['headers'][$name];
            }

            return $default;
        }

        return $this->meta;
    }

    public function meta(?string $name = null, mixed $default = null): mixed
    {
        return $this->getMeta($name, $default);
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            '@metadata' => $this->meta
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        if (array_key_exists($offset, $this->data)) {
            return true;
        }

        if (array_key_exists($offset, $this->meta)) {
            return true;
        }

        if (array_key_exists($offset, $this->meta['headers'])) {
            return true;
        }

        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (array_key_exists($offset, $this->data)) {
            return $this->data[$offset];
        }

        if (array_key_exists($offset, $this->meta)) {
            return $this->meta[$offset];
        }

        if (array_key_exists($offset, $this->meta['headers'])) {
            return $this->meta['headers'][$offset];
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {

    }

    public function offsetUnset(mixed $offset): void
    {

    }
}