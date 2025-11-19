<?php declare(strict_types = 1);

namespace Imhotep\Validation\Rules;

use DateTimeInterface;

class DateAfterRule extends DateRule
{
    protected ?string $message = ":attribute must be after :date";

    public function setParameters(array $parameters): static
    {
        $this->parameters['date'] = array_shift($parameters);

        return $this;
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['date']);

        $field = $this->parameter('date');

        if ($this->data->has($field)) {
            $afterDate = $this->makeDatetime($this->data->get($field));
        }
        else {
            $afterDate = $this->makeDatetime($field);
        }

        if (! $afterDate instanceof DateTimeInterface) {
            return false;
        }

        return parent::check($value) && $value > $afterDate;
    }
}