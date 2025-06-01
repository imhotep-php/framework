<?php declare(strict_types = 1);

namespace Imhotep\Validation\Rules;

class ClosureRule extends AbstractRule
{
    protected string $key = 'closure';

    public function __construct(
        protected \Closure $callback
    ) {}

    public function check(mixed $value): bool
    {
        $this->failed = false;

        // string $message, bool $implicit = false

        $this->callback->__invoke($this->attribute->key(), $value, function () {
            $this->failed = true;

            //$this->implicit = $implicit;

            //$this->message = $message;

            return new class ($this)
            {
                public function __construct(
                    protected ClosureRule $rule
                ) {}

                public function message(string $message): static
                {
                    $this->rule->setMessage($message);

                    return $this;
                }

                public function name(string $name): static
                {
                    $this->rule->setName($name);

                    return $this;
                }

                public function implicit(): static
                {
                    $this->rule->setImplicit(true);

                    return $this;
                }
            };
        });

        return ! $this->failed;
    }
}