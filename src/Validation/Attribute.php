<?php declare(strict_types=1);

namespace Imhotep\Validation;

use Imhotep\Contracts\Validation\IValidator;
use Imhotep\Contracts\Validation\IModifyValue;
use Imhotep\Validation\Data\InputData;

class Attribute
{
    protected array $rules = [];

    protected array $ruleNames = [];

    protected bool $bail = false;

    public function __construct(
        protected string       $key,
        protected string       $name,
        string|array           $rules
    )
    {
        $parser = RuleParser::parse($rules);

        $this->rules = $parser['rules'];
        $this->ruleNames = $parser['names'];
        $this->bail = $parser['bail'];
    }

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function bail(): bool
    {
        return $this->bail;
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function hasRule(string $name): bool
    {
        return in_array($name, $this->ruleNames);
    }
}