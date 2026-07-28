<?php declare(strict_types=1);

namespace Imhotep\Http\Traits;

use Imhotep\Http\HeaderBag;

trait HasHeaders
{
    public HeaderBag $headers;


    public function headers(string|array|null $keys = null): array
    {
        if (is_null($keys)) {
            return $this->headers->all();
        }

        return $this->headers->only(is_array($keys) ? $keys : func_get_args());
    }

    public function setHeaders(array $headers = []): static
    {
        $this->headers->add($headers);

        return $this;
    }

    public function hasHeaders(string|array $keys): bool
    {
        return $this->headers->hasMany(is_array($keys) ? $keys : func_get_args());
    }

    public function removeHeaders(string|array $keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            $this->headers->remove($key);
        }

        return $this;
    }


    public function header(string $key, mixed $default = null): ?string
    {
        return $this->headers->get($key, $default);
    }

    public function setHeader(string $key, string $value): static
    {
        $this->headers->set($key, $value);

        return $this;
    }

    public function hasHeader(string $key): bool
    {
        return $this->headers->has($key);
    }

    public function removeHeader(string $key): static
    {
        $this->headers->remove($key);

        return $this;
    }
}