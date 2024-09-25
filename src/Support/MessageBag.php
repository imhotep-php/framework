<?php declare(strict_types=1);

namespace Imhotep\Support;

use Imhotep\Contracts\Arrayable;
use Imhotep\Contracts\Support\MessageBag as MessageBagContract;

class MessageBag implements MessageBagContract
{
    protected array $messages = [];

    protected string $format = ":message";

    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            $value = $value instanceof Arrayable ? $value->toArray() : (array)$value;

            foreach ($value as $k => $v) {
                if (! is_string($v) || empty($v)) {
                    unset($value[$k]);
                }
            }

            if (! empty($value)) {
                $this->messages[$key] = array_unique($value);
            }
        }
    }

    public function messages(): array
    {
        return $this->messages;
    }

    // Есть ли в наличии какие-либо сообщения
    public function any(): bool
    {
        return $this->count() > 0;
    }

    public function isEmpty(): bool
    {
        return ! $this->any();
    }

    public function isNotEmpty(): bool
    {
        return $this->any();
    }

    // Количество сообщений в пакете
    public function count(): int
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }

    // Возвращает все ключи сообщений в пакете
    public function keys(): array
    {
        return array_keys($this->messages);
    }

    public function get(string $key, string $format = null): array
    {
        if (! array_key_exists($key, $this->messages)) {
            return [];
        }

        return $this->transform($this->messages[$key], $key, $format) ;
    }

    public function all(string $format = null): array
    {
        $result = [];
        foreach ($this->messages as $key => $value) {
            $result = array_merge($result, $this->transform($value, $key, $format));
        }

        return $result;
    }

    public function unique(string $format = null): array
    {
        return array_unique($this->all($format));
    }

    public function firstOfAll(string $format = null): array
    {
        $result = [];
        foreach ($this->messages as $key => $value) {
            if (count($value) > 0) {
                $result = array_merge($result, $this->transform([$value[0]], $key, $format));
            }
        }

        return $result;
    }

    public function first(string $key = null, string $format = null): string
    {
        if (is_null($key)) {
            $key = array_key_first($this->messages);
        }

        $messages = $this->transform($this->messages[$key], $key, $format);

        return $messages[0] ?: '';
    }

    protected function transform(array $messages, string $key, string $format = null): array
    {
        $format = $format ?: $this->format;

        if ($format === ':message') {
            return $messages;
        }

        $result = [];
        foreach ($messages as $message) {
            $result[] = str_replace([':message', ':key'], [$message, $key], $format);
        }

        return $result;
    }

    // Определяем наличие сообщений для каждого переданного ключа
    public function has(array|string $key = null): bool
    {
        if (is_null($key) || $this->isEmpty()) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->messages)) {
                return false;
            }

            if ($this->messages[$key][0] === '') {
                return false;
            }
        }

        return true;
    }

    // Определяем наличие сообщений для любого из переданного ключа
    public function hasAny(array|string $key = null): bool
    {
        if (is_null($key) || $this->isEmpty()) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function add(array|string $key, array|string $value = null): static
    {
        if (is_string($key) && is_string($value)) {
            if ($this->isUnique($key, $value)) {
                //if (! is_array($this->messages)) $this->messages = [];

                $this->messages[$key][] = $value;
            }
        }

        elseif (is_string($key) && is_array($value)) {
            foreach ($value as $message) {
                if (is_string($message)) {
                    $this->add($key, $message);
                }
            }
        }

        elseif (is_array($key)) {
            foreach ($key as $k => $v) {
                if (is_string($v) || is_array($v)) {
                    $this->add($k, $v);
                }
            }
        }

        return $this;
    }

    public function addIf(bool $boolean, array|string $key, array|string $value = null): static
    {
        return ($boolean) ? $this->add($key, $value) : $this;
    }

    protected function isUnique(string $key, string $message): bool
    {
        return ! isset($this->messages[$key]) || ! in_array($message, $this->messages[$key]);
    }

    public function format(string $format = null): static|string
    {
        if (is_null($format)) {
            return $this->getFormat();
        }

        return $this->setFormat($format);
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }
}