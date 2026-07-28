<?php declare(strict_types=1);

namespace Imhotep\Http;

use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

class HeaderBag extends ParameterBag
{
    public function getArray(string $key): array
    {
        $value = parent::get($key, []);

        return is_array($value) ? $value : [$value];
    }

    public function getLine(string $key): string
    {
        $value = parent::get($key);

        return is_array($value) ? implode(', ', $value) : $value;
    }

    public function getDate(string $key, ?DateTimeInterface $default = null): ?DateTimeInterface
    {
        if (null === $value = $this->get($key)) {
            return null !== $default ? DateTimeImmutable::createFromInterface($default) : null;
        }

        if (false === $date = DateTimeImmutable::createFromFormat(DATE_RFC2822, $value)) {
            throw new RuntimeException(sprintf('The "%s" HTTP header is not parseable (%s).', $key, $value));
        }

        return $date;
    }

    protected function modifyKey(mixed $key): mixed
    {
        return str_replace('_', '-', strtolower($key));
    }

    public function normalizeName(string $key): string
    {
        return preg_replace_callback('/\b[a-z]/', function($matches) {
            return strtoupper($matches[0]);
        }, $key);
    }
}