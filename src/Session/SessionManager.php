<?php declare(strict_types=1);

namespace Imhotep\Session;

use Imhotep\Contracts\DriverManager;
use Imhotep\Contracts\Session\SessionException;
use Imhotep\Contracts\Session\ISession;
use Imhotep\Filesystem\Filesystem;
use Imhotep\Session\Handlers\ArrayHandler;
use Imhotep\Session\Handlers\CacheHandler;
use Imhotep\Session\Handlers\CookieHandler;
use Imhotep\Session\Handlers\DatabaseHandler;
use Imhotep\Session\Handlers\FileHandler;
use InvalidArgumentException;
use SessionHandlerInterface;

class SessionManager extends DriverManager
{
    protected ?ISession $store = null;

    public function store(): ISession
    {
        if ($this->store) {
            return $this->store;
        }

        if ($this->config['session.encrypt']) {
            return $this->store = new EncryptedStore(
                $this->container->make('encrypter'),
                $this->driver($this->getDefaultDriver()),
                $this->config->get('session', [])
            );
        }

        return $this->store = new Store(
            $this->driver($this->getDefaultDriver()),
            $this->config->get('session', [])
        );
    }

    protected function createArrayDriver(): SessionHandlerInterface
    {
        return new ArrayHandler($this->config->get('session', []));
    }

    protected function createFileDriver(): SessionHandlerInterface
    {
        $path = $this->config->get('session.files');

        if (! is_dir($path)) {
            $path = (string)$path;

            throw new SessionException("Parameter [files] not configured in session driver. The path [$path] is not a directory.");
        }

        return new FileHandler(new Filesystem(), $path, $this->getLifetime());
    }

    protected function createCacheDriver(): SessionHandlerInterface
    {
        $cache = $this->container->make('cache')->store(
            $this->config->get('session.store')
        );

        return new CacheHandler($cache, $this->getLifetime());
    }

    protected function createCookieDriver(): SessionHandlerInterface
    {
        return new CookieHandler($this->container->make('cookie'), $this->getLifetime());
    }

    protected function createDatabaseDriver(): SessionHandlerInterface
    {
        $connection = $this->container->make('db')->connection(
            $this->config->get('session.connection')
        );

        return new DatabaseHandler(
            $connection,
            $this->config->get('session.table', ''),
            $this->getLifetime()
        );
    }

    public function getLifetime(): int
    {
        $lifetime = $this->config->get('session.lifetime', 300);

        return is_integer($lifetime) ? $lifetime : 300;
    }

    public function getDefaultDriver(): string
    {
        return $this->config['session.driver'];
    }

    public function setDefaultDriver(string $driver): static
    {
        $this->config['session.driver'] = $driver;

        return $this;
    }

    public function __call($method, $parameters)
    {
        $store = $this->store();

        if (method_exists($store, $method)) {
            return $store->$method(...$parameters);
        }

        throw new InvalidArgumentException("Method [$method] not supported in [".static::class."].");
    }
}