<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Contracts\Validation\ModifyValue;
use Imhotep\Support\Str;
use Imhotep\Validation\Rule;

class Email extends Rule implements ModifyValue
{
    protected string $message = 'The :attribute is not valid email';

    protected array $flags = [
        'idn' => false,
        'dns' => false,
        'lower' => false,
        'filter' => false,
        'filter_unicode' => false,
    ];

    public function setParameters(array $parameters): static
    {
        foreach ($parameters as $param) {
            if (isset($this->flags[$param])) {
                $this->flags[$param] = true;
            }
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        if ($this->attribute && ! $this->attribute->hasRules('required') && empty($value)) {
            return true;
        }

        $value = (string)$value;

        if (substr_count($value, '@') !== 1) {
            return false;
        }

        if ($this->flags['idn']) {
            $exp = explode("@", $value);
            $value = $exp[0].'@'.idn_to_ascii($exp[1]);
        }

        $valid = true;

        if ($this->flags['filter_unicode']) {
            $valid = filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false;
        }
        elseif ($this->flags['filter']) {
            $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        if ($valid && $this->flags['dns']) {
            $exp = explode("@", $value);

            if (checkdnsrr($exp[1]) === false && checkdnsrr($exp[1], 'A') === false ) {
                $valid = false;
            }
        }

        return $valid;
    }

    public function modifyValue(mixed $value): mixed
    {
        return Str::lower($value);
    }
}