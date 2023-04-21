<?php

declare(strict_types=1);

namespace Imhotep\Dotenv;

class Parser
{
    protected array $data = [];

    public function parse(string $string): ?array
    {
        $lines = $this->getLines($string);

        for ($line = 0; $line < count($lines); $line++) {
            $string = $lines[$line];

            // Skip empty row
            if (trim($string) == '') continue;

            // Skip comment
            if (str_starts_with(trim($string), '#')) continue;

            // Invalid line
            if (! $this->isHasName($string)) {
                throw new DotenvException($string);
            }

            while (true) {
                $nextLine = $line+1;
                if (isset($lines[$nextLine]) && ! $this->isHasName($lines[$nextLine])) {
                    $string .= "\n".$lines[$nextLine]; $line++; continue;
                }
                break;
            }

            if (preg_match("/^([A-z0-9_.]+)(.*?)$/s", trim($string), $match)) {
                $this->data[ $match[1] ] = $this->parseValue(ltrim($match[2], ' ='));
            }
        }

        return $this->data;
    }

    protected function getLines(string $string): array
    {
        return preg_split("/(\r\n|\n|\r)/", $string);
    }

    protected function isHasName($string): bool
    {
        return (bool)preg_match("/^([A-z0-9_.]+)\s?=/", trim($string));
    }

    protected function parseValue(string $value): mixed
    {
        $quote = null;
        $symbols = trim($value);
        $symbolLenght = strlen($symbols);
        $value = '';

        for ($i=0; $i<$symbolLenght; $i++) {
            $symbol = $symbols[$i];

            // Determine is quote symbol
            if ($i === 0) {
                if (in_array($symbol, ['"',"'",'`'])) {
                    $quote = $symbol;
                    continue;
                }
            }

            // Read value only up to the first symbol of beginning a comment
            if (is_null($quote) && $symbol === '#') {
                break;
            }

            // Read the value up to the closing quote
            if (! is_null($quote) && $symbol === $quote) {
                if ($symbols[$i-1] !== '\\') {
                    break;
                }
                if ($symbols[$i-1] === '\\' && $symbols[$i-2] === '\\') {
                    break;
                }
            }

            $value .= $symbol;

            if (! is_null($quote) && $i === $symbolLenght-1) {
                throw new DotenvException($symbols);
            }
        }

        if (is_null($quote)) {
            return $this->parseValueMixed($value);
        }

        $value = str_replace("\\{$quote}", $quote, $value);
        $value = str_replace("\\\\", "\\", $value);

        return $this->parseValueNested($value, $quote);
    }

    protected function parseValueMixed(string $value): mixed
    {
        $value = trim($value);

        if ($value === '' || $value === 'null') return null;

        $valueLower = mb_strtolower($value, 'UTF-8');

        // Determine is boolean "true"
        if (preg_match('/^(on|yes|true)$/', $valueLower)) {
            return true;
        }

        // Determine is boolean "false"
        if (preg_match('/^(off|no|false)$/', $valueLower)) {
            return false;
        }

        // Determine is numeric "int" or "float"
        if (is_numeric($value)) {
            if (is_float($value + 0)) {
                return (float)$value;
            }

            return (int)$value;
        }

        return $value;
    }

    protected function parseValueNested($value, $quote): string
    {
        if ($quote != '"') {
            return $value;
        }

        if (! str_contains($value, '${')) {
            return $value;
        }

        $value = preg_replace_callback('/(\\\)?\${((\\\)?(\${([A-z0-9_.]+)}))}/', function($matches)
        {
            if ($matches[3] === '\\') return $matches[0];

            $value = $this->getValue($matches[5]);

            if (is_null($value)) {
                return $matches[0];
            }

            return str_replace($matches[4], $value, $matches[0]);
        }, $value);

        $value = preg_replace_callback('/(\\\)?\${([A-z0-9_.]+)}/', function($matches)
        {
            if ($matches[1] === '\\') return $matches[0];

            $value = $this->getValue($matches[2]);

            return is_null($value) ? $matches[0] : $value;
        }, $value);

        return str_replace('\\${', '${', $value);
    }

    protected function getValue($key): ?string
    {
        if (array_key_exists($key, $this->data)) {
            return (string)$this->data[$key];
        }
        elseif (array_key_exists($key, $_ENV)) {
            return (string)$_ENV[$key];
        }
        elseif ($value = getenv($key)) {
            return (string)$value;
        }

        return null;
    }

}