<?php

declare(strict_types=1);

namespace Imhotep\Auth;

use Closure;
use Imhotep\Auth\Guards\SessionGuard;
use Imhotep\Auth\Guards\TokenGuard;
use Imhotep\Auth\UserProviders\DatabaseUserProvider;
use Imhotep\Container\Container;
use Imhotep\Contracts\Auth\AuthenticationException;
use Imhotep\Contracts\Auth\Factory;
use Imhotep\Contracts\Auth\Guard;
use Imhotep\Contracts\Auth\UserProvider;
use Imhotep\Support\Timebox;

class AuthManager implements Factory
{
    protected Closure $userResolver;

    public function __construct(
        protected Container $app
    )
    {
        $this->userResolver = fn ($guard = null) => $this->guard($guard)->user();
    }

    protected array $guards = [];

    protected array $guardDrivers = [
        //'database' => DatabaseGuard::class,
        //'session'  => SessionGuard::class,
        //'token'    => TokenGuard::class
    ];

    public function guard(string $name = null): Guard
    {
        $name = empty($name) ? $this->getDefaultGuard() : $name;

        return $this->guards[$name] ?? $this->resolveGuard($name);
    }

    protected function resolveGuard(string $name = null): Guard
    {
        $config = $this->getGuardConfig($name);

        if (empty($config['driver'])) {
            throw new \Exception("Auth guard driver [{$name}] is not configured.");
        }

        $methodName = 'create'.ucfirst($config['driver']).'Driver';
        if (method_exists($this, $methodName)) {
            $this->guards[$name] = $this->{$methodName}($name, $config);
        }
        else {
            $driver = $this->guardDrivers[ $config['driver'] ] ?? null;

            if ($driver instanceof Closure) {
                $this->guards[$name] = $this->app->build($driver, [$this->app, $config]);
            }
        }

        if (empty($this->guards[$name])) {
            throw new \Exception("Auth guard driver [{$name}] is not supported.");
        }

        $this->guards[$name]->setProvider(
            $this->provider((string)$config['provider'], $name)
        );
        $this->guards[$name]->setEvents($this->app['events']);
        $this->guards[$name]->setRequest($this->app['request']);

        return $this->guards[$name];
    }

    public function getDefaultGuard(): string
    {
        $name = $this->app['config']["auth.defaults.guard"];

        if (empty($name)) {
            throw new \Exception("Default auth guard is not configured.");
        }

        return $name;
    }

    public function getGuardConfig(string $name): array
    {
        $config = $this->app['config']["auth.guards.$name"];

        if (is_null($config)) {
            throw new \Exception("Auth guard [$name] is not configured.");
        }

        return (array)$config;
    }

    public function extendGuard(string $guard, Closure $callback): static{
        if (isset($this->guardDrivers[$guard])) {
            unset($this->guardDrivers[$guard]);
        }

        $this->guardDrivers[$guard] = $callback;

        return $this;
    }



    protected array $providers = [];

    protected array $providerDrivers = [
        'database' => DatabaseUserProvider::class
    ];

    protected function provider(string $name, string $guard): UserProvider
    {
        if (empty($name)) {
            throw new \Exception("Auth provider for guard [{$guard}] is not configured.");
        }

        return $this->providers[$name] ?? $this->resolveProvider($name);
    }

    protected function resolveProvider(string $name): UserProvider
    {
        $config = $this->getProviderConfig($name);

        if (empty($config['driver'])) {
            throw new \Exception("Parameter [driver] for auth provider [{$name}] is not configured.");
        }

        $methodName = 'create'.ucfirst($config['driver']).'UserProvider';
        if (method_exists($this, $methodName)) {
            $this->providers[$name] = $this->{$methodName}($config);
        }
        else {
            $driver = $this->providerDrivers[ $config['driver'] ] ?? null;

            if ($driver instanceof Closure) {
                $this->providers[$name] = $this->app->build($driver, [$this->app, $config]);
            }
        }

        if (empty($this->providers[$name])) {
            throw new \Exception("Auth provider [{$name}] is not supported.");
        }

        return $this->providers[$name];
    }

    protected function getProviderConfig(string $name): array
    {
        $config = $this->app['config']["auth.providers.$name"];

        if (is_null($config)) {
            throw new \Exception("Auth provider [$name] is not configured.");
        }

        return (array)$config;
    }

    public function extendProvider(string $provider, Closure $callback): static{
        if (isset($this->providerDrivers[$provider])) {
            unset($this->providerDrivers[$provider]);
        }

        $this->providerDrivers[$provider] = $callback;

        return $this;
    }

    public function shouldUse(string $guard)
    {
        // TODO: Implement shouldUse() method.
    }


    public function createTokenDriver(string $name, array $config): Guard
    {
        return new TokenGuard(
            empty($config['input_key']) ? 'auth_token' : $config['input_key'],
            empty($config['storage_key']) ? 'auth_token' : $config['storage_key'],
            $config['hash'] ?? false,
        );
    }

    public function createSessionDriver(string $name, array $config): Guard
    {
        $config['remember'] = isset($config['remember']) ? abs((int)$config['remember']) : 0;

        return new SessionGuard(
            $name,
            $this->app['session.store'],
            $this->app['cookie'],
            new Timebox(),
            $config['remember']
        );
    }

    public function createRequestDriver(): Guard
    {

    }

    public function createDatabaseUserProvider(array $config): UserProvider
    {
        if (empty($config['table'])) {
            throw new AuthenticationException('Parameter [table] for auth provider [database] is not configured.');
        }

        return new DatabaseUserProvider(
            $this->app['db']->connection($config['connection'] ?? null),
            $config['table']
        );
    }

    public function userResolver(): Closure
    {
        return $this->userResolver;
    }

    public function resolveUsersUsing(Closure $resolver): static
    {
        $this->userResolver = $resolver;

        return $this;
    }

    /**
     * Dynamically call the default guard instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->guard()->{$method}(...$parameters);
    }
}