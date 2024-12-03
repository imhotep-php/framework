<?php declare(strict_types=1);

namespace Imhotep\Console\Input;

class StringInput extends ArgvInput
{
    protected array $parameters;

    public function __construct(string $input)
    {
        parent::__construct([]);

        $this->argv = $this->convertToArgv($input);
    }

    protected function convertToArgv(string $input): array
    {
        $vars = [];
        $length = strlen($input);
        $cursor = 0;
        $result = '';

        while($cursor < $length) {
            $pos1 = strpos($input, "'", $cursor);
            $pos2 = strpos($input, '"', $cursor);
            $pos = min(
                $pos1 !== false ? $pos1 : 100000,
                $pos2 !== false ? $pos2 : 100000
            );

            if ($pos === 100000) break;

            if ($match = $this->parseQuote($input, $pos)) {
                $key = '_IMHOTEP_QUOTE_VAR_'.count($vars).'_';
                $vars[$key] = $match[1];

                $result .= substr($input, $cursor, $pos - $cursor);
                $cursor += $pos - $cursor;

                $result .= $key;
                $cursor += strlen($match[0]);
            }
            else {
                $strlen = ($pos - $cursor) + 1;
                $result .= substr($input, $cursor, $strlen);
                $cursor += $strlen;
            }
        }

        $result .= substr($input, $cursor);

        $input = preg_replace('/\s+/', ' ', stripcslashes($result));
        $input = explode(' ', $input);

        if (count($vars) > 0) {
            $search = []; $replace = [];
            foreach ($vars as $key => $val) {
                $search[] = $key;
                $replace[] = $val;
            }

            foreach ($input as $index => $line) {
                $input[$index] = str_replace($search, $replace, $line);
            }
        }

        return array_values(array_filter($input));
    }

    protected function parseQuote(string $input, int $cursor): ?array
    {
        $quote = $input[$cursor];

        if ($this->quoteEscaped($input, $cursor)) {
            return null;
        }

        $offset = 1; $length = 0;
        while ($pos = strpos($input, $quote, $cursor + $offset)) {
            $length = $pos - $cursor + 1;

            if (! $this->quoteEscaped($input, $pos)) break;

            $offset = $length;
        }

        $value = substr($input, $cursor, $length);

        return [$value, trim(stripcslashes($value), $quote)];
    }

    protected function quoteEscaped(string $input, int $position): bool
    {
        $count = 0;

        while ($position--) {
            if ($input[$position] === '=') {
                break;
            }

            if ($position >= 0 && $input[$position] === '\\') {
                $count++;
                continue;
            }

            break;
        }

        return $count !== 0;
    }
}