<?php declare(strict_types=1);

namespace Imhotep\Closure;

use Closure;
use function Opis\Closure\{init, serialize, unserialize};

class SerializableClosure
{
    protected Closure $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function __invoke()
    {
        return call_user_func_array($this->closure, func_get_args());
    }

    public function getClosure(): Closure
    {
        return $this->closure;
    }

    /**
     * Set the serializable closure secret key.
     */
    public function setSecretKey(string $key): void
    {
        init($key);
    }

    /**
     * Get the serializable representation of the closure.
     */
    public function __serialize()
    {
        return ['serializable' => serialize($this->closure)];
    }

    /**
     * Restore the closure after serialization.
     */
    public function __unserialize(array $data)
    {
        $this->closure = unserialize($data['serializable']);
    }
}