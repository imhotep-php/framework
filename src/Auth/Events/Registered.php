<?php declare(strict_types=1);

namespace Imhotep\Auth\Events;

use Imhotep\Contracts\Auth\Authenticatable;

class Registered
{
    /**
     * The authenticated user.
     *
     * @var Authenticatable
     */
    public Authenticatable $user;

    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $user
     * @return void
     */
    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }
}
