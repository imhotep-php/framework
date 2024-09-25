<?php declare(strict_types=1);

namespace Imhotep\Auth\Events;

use Imhotep\Contracts\Auth\Authenticatable;

class Login
{
    /**
     * Create a new event instance.
     *
     * @param string $guard
     * @param Authenticatable|null $user
     * @param bool $remember
     */
    public function __construct(
        // The authentication guard name.
        public string $guard,

        // The authenticated user.
        public ?Authenticatable $user,

        // Indicates if the user should be "remembered".
        public bool $remember
    ) { }
}
