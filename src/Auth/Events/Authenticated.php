<?php declare(strict_types=1);

namespace Imhotep\Auth\Events;

use Imhotep\Contracts\Auth\Authenticatable;

class Authenticated
{
    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  Authenticatable  $user
     * @return void
     */
    public function __construct(
        // The authentication guard name.
        public string $guard,

        // The user the attempter was trying to authenticate as.
        public Authenticatable $user
    ) { }
}
