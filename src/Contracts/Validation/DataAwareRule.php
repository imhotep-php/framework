<?php declare(strict_types=1);

namespace Imhotep\Contracts\Validation;

interface DataAwareRule
{
    public function setData(array $data);
}