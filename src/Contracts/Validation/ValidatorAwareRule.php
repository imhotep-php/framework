<?php declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

use Imhotep\Validation\Validator;

interface ValidatorAwareRule
{
    public function setValidator(Validator $data);
}