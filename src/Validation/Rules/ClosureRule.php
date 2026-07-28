<?php declare(strict_types = 1);

namespace Imhotep\Validation\Rules;

use Closure;
use Imhotep\Contracts\Validation\DataAwareRule;
use Imhotep\Contracts\Validation\IValidationRule;
use Imhotep\Contracts\Validation\ValidatorAwareRule;
use Imhotep\Validation\Attribute;
use Imhotep\Validation\Validator;

class ClosureRule extends AbstractRule
{
    protected string $key = 'closure';

    protected Validator $validator;

    protected Attribute $attribute;

    protected bool $failed = false;

    public function __construct(
        protected IValidationRule|Closure $callback
    ) {}

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    public function setAttribute(Attribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function check(mixed $value): bool
    {
        $fail = function (?string $message = null, bool $implicit = false) {
            $this->failed = true;

            if (is_string($message)) {
                $this->setMessage($message);
            }

            if ($implicit) {
                return $this->implicit;
            }

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
        };

        $methodName = '__invoke';

        if ($this->callback instanceof IValidationRule) {
            $methodName = 'validate';

            if ($this->callback instanceof DataAwareRule) {
                $this->callback->setData($this->data->toArray());
            }

            if ($this->callback instanceof ValidatorAwareRule) {
                $this->callback->setValidator($this->validator);
            }
        }

        $this->callback->{$methodName}($this->attribute?->key(), $value, $fail);

        return ! $this->failed;
    }
}