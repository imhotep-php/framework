<?php declare(strict_types=1);

namespace Imhotep\Console\Utils;

use Imhotep\Console\Input\InputArgument;
use Imhotep\Console\Input\InputOption;
use Imhotep\Contracts\Console\ConsoleException;
use InvalidArgumentException;

class SignatureParser
{
    public static function parse(string $signature): ?array
    {
        $signature = preg_replace('/\s+/', ' ', trim($signature));

        if (empty($signature)) {
            return null;
        }

        [$name, $description] = static::info($signature);

        $result = [
            'name' => $name,
            'description' => $description,
            'arguments' => [],
            'options' => [],
        ];

        if (preg_match_all('/{(.*?)}/', $signature, $matches)) {
            [$result['arguments'], $result['options']] = static::parameters($matches[1]);
        }

        return $result;
    }

    public static function info(string $signature): array
    {
        if (preg_match('/^([A-z:]+)(?:\s*-\s*(.*?))?(?:{|$)/', $signature, $match)) {
            return [$match[1], isset($match[2]) ? trim($match[2]) : ''];
        }

        return ['', ''];
    }

    public static function name(string $signature): ?string
    {
        if (preg_match('/^([A-z:]+)/', $signature, $match)) {
            return $match[1];
        }

        return null;
    }

    public static function parameters(array $expressions): array
    {
        $arguments = $options = [];

        foreach ($expressions as $expression) {
            $expression = trim($expression);

            if (str_starts_with($expression, '--')) {
                $options[] = static::option($expression);
            }
            else {
                $arguments[] = static::argument($expression);
            }
        }

        return [$arguments, $options];
    }

    public static function argument(string $expression): InputArgument
    {
        list($exp, $desc) = static::extractDescription($expression);

        if (! preg_match('/^([A-z0-9\-]+)([?*]+)?(?:=(.*))?$/', $exp, $match)) {
            throw new InvalidArgumentException('Invalid command signature for argument {' . $exp .'}');
        }

        $isArray = isset($match[2]) && str_contains($match[2], '*');
        $isRequired = ! (isset($match[2]) && str_contains($match[2], '?'));

        $argument = InputArgument::builder($match[1]);

        if ($isArray) $argument->array();
        if ($isRequired) $argument->required();
        if (! empty($match[3])) $argument->default(trim($match[3]));

        return $argument->description($desc)->build();
    }

    public static function option(string $expression): ?InputOption
    {
        list($exp, $desc) = static::extractDescription($expression);

        if (! preg_match('/^--(?:([A-z])\|)?([A-z0-9\-]+)(?:(=)([?*]+)?(.*))?$/', $exp, $match)) {
            throw new ConsoleException('Invalid command signature for option: ' . $exp);
        }

        $shortcut = empty($match[1]) ? null : $match[1];
        $isArray = isset($match[4]) && str_contains($match[4], '*');
        $isValueOptional = isset($match[4]) && str_contains($match[4], '?');
        $isValueRequired = (isset($match[3]) && $match[3] === '=') && ! $isValueOptional;

        $option = InputOption::builder($match[2], $shortcut);

        if ($isArray) $option->array();
        if ($isValueOptional) $option->valueOptional();
        if ($isValueRequired) $option->valueRequired();
        if (! empty($match[5])) $option->default(trim($match[5]));

        return $option->description($desc)->build();
    }

    protected static function extractDescription(string $expression): array
    {
        $exp = explode(':', $expression, 2);
        $exp = count($exp) === 2 ? $exp : [$exp[0], ''];

        return [trim($exp[0]), trim($exp[1])];
    }
}