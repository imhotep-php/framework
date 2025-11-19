<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class UrlRule extends AbstractRule
{
    protected array $allowedSchemes = ['http', 'https'];

    public function setParameters(array $parameters): static
    {
        if (!empty($parameters)) {
            $this->allowedSchemes = $parameters;
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        // Базовая проверка формата URL
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Проверка наличия схемы и хоста
        $parsed = parse_url($value);
        if (! isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // Проверка схемы
        return in_array(strtolower($parsed['scheme']), $this->allowedSchemes);
    }
}