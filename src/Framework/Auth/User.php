<?php

namespace Imhotep\Framework\Auth;

use Imhotep\Auth\Traits\Authenticatable;
use Imhotep\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Imhotep\Database\Model\Model;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected bool $timestamps = true;

    protected bool $softDelete = true;
}