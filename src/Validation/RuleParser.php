<?php declare(strict_types = 1);

namespace Imhotep\Validation;

use Imhotep\Validation\Rules\ClosureRule;

class RuleParser
{
    public static array $rules = [
        'accepted' => Rules\AcceptedRule::class,
        'accepted_if' => Rules\AcceptedIfRule::class,
        'required' => Rules\RequiredRule::class,
        'required_if' => Rules\RequiredRuleIfRule::class,
        'required_unless' => Rules\RequiredRuleUnlessRule::class,
        'required_without' => Rules\RequiredWithoutRule::class,
        'email' => Rules\EmailRule::class,
        'lowercase' => Rules\LowercaseRule::class,
        'uppercase' => Rules\UppercaseRule::class,
        'same' => Rules\SameRule::class,
        'different' => Rules\DifferentRule::class,
        'file' => Rules\FileRule::class,
        'image' => Rules\ImageRule::class,
        'max' => Rules\MaxRule::class,
        'min' => Rules\MinRule::class,
        'dimensions' => Rules\DimensionsRule::class,
        'nullable' => Rules\NullableRule::class,
        'string' => Rules\StringRule::class,
        'int' => Rules\IntegerRule::class,
        'float' => Rules\FloatRule::class,
        'bool' => Rules\BooleanRule::class,
        'array' => Rules\ArrayRule::class,
        'gt' => Rules\GreaterThanRule::class,
        'gte' => Rules\GreaterThanOrEqualRule::class,
        'size' => Rules\SizeRule::class,
        'phone' => Rules\PhoneRule::class,
        'in' => Rules\InRule::class,
    ];
    
    public static function parse(string|array $rules): array
    {
        $result = [
            'rules' => [],
            'names' => [],
            'bail' => false,
        ];

        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $result['names'][] = $rule;

                if ($rule === 'bail') {
                    $result['bail'] = true;

                    continue;
                }
            }

            $result['rules'][] = static::parseRule($rule, false);
        }

        return $result;
    }

    protected static function parseRule(mixed $rule, ?bool $bail): mixed
    {
        if (is_string($rule)) {
            $parameters = [];

            if (str_contains($rule, ':')) {
                list($rule, $parameters) = explode(":", $rule, 2);
                $parameters = explode(",", $parameters);
            }
        }
        elseif (is_array($rule)) {
            $parameters = array_slice($rule, 1);
            $rule = $rule[0];
        }
        elseif ($rule instanceof \Closure) {
            return new ClosureRule($rule);
        }

        if (! isset(static::$rules[$rule])) {
            throw new \InvalidArgumentException("Rule [$rule] does not exist.");
        }

        $instance = static::$rules[$rule];

        if ($instance instanceof \Closure) {
            $instance = new ClosureRule($instance);
        }
        else {
            $instance = (new $instance())->setParameters($parameters)->setName($rule);
        }

        return $instance;
    }
}