<?php declare(strict_types=1);

namespace Imhotep\Auth\Events;

use Imhotep\Contracts\Auth\Authenticatable;

class Logout
{
    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  Authenticatable  $user
     * @return void
     */
    public function __construct(
        public string $guard,
        public Authenticatable $user
    ) { }
}
