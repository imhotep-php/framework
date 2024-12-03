<?php declare(strict_types = 1);

namespace Imhotep\Auth\Events;

use Imhotep\Contracts\Auth\Authenticatable;

class Validated
{
    public function __construct(
        // The authentication guard name.
        public string $guard,

        // The validated user.
        public Authenticatable $user,

        // The credentials for validation.
        public array $credentials
    ) { }
}
