<?php declare(strict_types = 1);

namespace Imhotep\Validation\Rules;

use DateTime;
use DateTimeInterface;

class DateBeforeRule extends DateRule
{
    protected ?string $message = ":attribute must be before :date";

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
            $beforeDate = $this->makeDatetime($this->data->get($field));
        }
        else {
            $beforeDate = $this->makeDatetime($field);
        }

        if (! $beforeDate instanceof DateTimeInterface) {
            return false;
        }

        return parent::check($value) && $value < $beforeDate;
    }


}