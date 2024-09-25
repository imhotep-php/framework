<?php declare(strict_types=1);

namespace Imhotep\Auth\Events;

class Attempting
{
    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    public function __construct(
        // The authentication guard name.
        public string $guard,

        // The credentials for the user.
        public array $credentials,

        // Indicates if the user should be "remembered".
        public bool $remember
    ) { }
}