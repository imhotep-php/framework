<?php declare(strict_types=1);

namespace Imhotep\Database\Query\Traits;

use InvalidArgumentException;

trait PrepareWhereExpression
{
    protected array $whereOperators = [
        '=', '>', '<', '>=', '<=', '<>', '!=', 'like', 'not like'
    ];

    protected function parseWhereExpression(string $expression): array
    {
        $reColumn = "([\w_.]+)";
        $reOperator = "(" . implode("|", $this->whereOperators) . ")";
        $reValue = "([\"']?)(.*?)\\1";

        $pattern = "/^{$reColumn}\s*{$reOperator}\s*{$reValue}$/s";

        if (! preg_match($pattern, $expression, $matches)) {
            throw new InvalidArgumentException(
                "Invalid WHERE expression: '{$expression}'. Expected format: 'column operator value'"
            );
        }

        return [
            $matches[1], // column
            $matches[2], // operator
            $matches[4]  // value (игнорируем кавычки из $matches[3])
        ];
    }

    protected function prepareWhere($condition): array
    {
        $parsed = ['column' => '', 'operator' => '', 'value' => ''];

        $args = array_filter($condition);

        // where('name = name')
        if (count($args) == 1) {
            $reColumn = "([\w_.]+)";
            $reOperator = "(".implode("|", $this->whereOperators).")";
            $reValue = "[\"']?(.*?)[\"']?";

            if (preg_match("/^{$reColumn}\s{0,}{$reOperator}\s{0,}{$reValue}$/", $args[0], $match)) {
                $parsed['column'] = $match[1];
                $parsed['operator'] = $match[2];
                $parsed['value'] = $match[3];
            }
            else {
                throw new \Exception("Query where invalid");
            }
        }

        // where('name', $name), '=' as default operator
        elseif (count($args) == 2) {
            $parsed['column'] = $args[0];
            $parsed['operator'] = '=';
            $parsed['value'] = $args[1];
        }

        // where('name', '!=', $name)
        elseif (count($args) == 3) {
            $parsed['column'] = $args[0];
            $parsed['operator'] = $args[1];
            $parsed['value'] = $args[2];
        }

        if (! $this->isValidWhereOperator($parsed['operator'])) {
            throw new \Exception("Operator '{$parsed['operator']}' is not supported.");
        }


        $this->addBinding($parsed['value'], 'where');

        return $parsed;
    }

    protected function isValidWhereOperator(string $operator): bool
    {
        return in_array($operator, $this->whereOperators);
    }

}