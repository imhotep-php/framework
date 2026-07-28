<?php declare(strict_types=1);

namespace Imhotep\Localization;

class Expression
{
    public string $expression;

    public int $number = 0;

    public array $values = [];

    public array $choiceRanges = [];

    public bool $isPlural = false;

    public bool $isChoice = false;

    public static array $plurals = [];

    public static function parse(string $text): array
    {
        preg_match_all("/{([^}]+)}/", $text, $matches);

        $expressions = [];

        foreach ($matches[1] as $index => $values) {
            $values = explode('|', $values);

            $number = trim(array_shift($values));

            if (is_numeric($number)) {
                $expressions[] = new Expression($matches[0][$index], (int)$number, $values);
            }
        }

        return $expressions;
    }

    public function __construct(string $expression, int $number, array $values)
    {
        $this->expression = $expression;

        $this->number = $number;

        $this->values = $values;

        foreach ($values as $value) {
            $value = trim($value);

            if ($this->isChoiceRange($value, $matches)) {
                $this->isChoice = true;

                $this->choiceRanges[] = [
                    'min' => $matches[1] ?? $matches[2],
                    'max' => $matches[2],
                    'value' => trim(str_replace($matches[0], '', $value))
                ];
            }
        }

        if (! $this->isChoice) {
            $this->isPlural = true;
        }
    }

    protected function isChoiceRange(string $value, &$matches): bool
    {
        return (bool)preg_match("/\[(?:([\d*]+),)?([\d*]+)\]/s", $value, $matches);
    }

    public function apply(string $text, string $locale): string
    {
        if ($this->isPlural) {
            $plural = $this->plural($locale);

            if (isset($this->values[$plural])) {
                return str_replace($this->expression, trim($this->values[$plural]), $text);
            }

            // Fallback to last value if plural form not found
            if (! empty($this->values)) {
                return str_replace($this->expression, trim(end($this->values)), $text);
            }
        }

        if ($this->isChoice) {
            $format = function ($value) {
                if (is_numeric($value)) return (int)$value;
                if ($value === '*') return $value;
                return null;
            };

            foreach ($this->choiceRanges as $range) {
                $min = $format($range['min']);
                $max = $format($range['max']);
                if ($min === '*') $min = $this->number-1;
                if ($max === '*') $max = $this->number+1;

                if ($this->number >= $min && $this->number <= $max) {
                    return str_replace($this->expression, trim($range['value']), $text);
                }
            }

            // Fallback to last choice range if no match found
            if (! empty($this->choiceRanges)) {
                $lastRange = end($this->choiceRanges);
                return str_replace($this->expression, trim($lastRange['value']), $text);
            }
        }

        return $text;
    }

    protected function plural(string $locale): int
    {
        if (isset(static::$plurals[$locale])) {
            $plural = static::$plurals[$locale]($this->number);

            if (is_int($plural)) {
                return $plural;
            }
        }

        return PluralRules::get($locale)($this->number);
    }
}
