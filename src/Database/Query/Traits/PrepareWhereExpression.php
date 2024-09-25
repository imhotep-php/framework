<?php declare(strict_types=1);

namespace Imhotep\Database\Query\Traits;

trait PrepareWhereExpression
{
    protected array $whereOperators = [
        '=', '>', '<', '>=', '<=', '<>', '!=', 'like', 'not like'
    ];

    protected function prepareWhere($condition): array
    {
        $parsed = ['column' => '', 'operator' => '', 'value' => ''];

        $args = $condition;

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

        return $parsed;
    }

    protected function isValidWhereOperator(string $operator): bool
    {
        return in_array($operator, $this->whereOperators);
    }

}