<?php declare(strict_types=1);

namespace Imhotep\Database;

use Closure;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Database\IConnection as ConnectionContract;
use Imhotep\Contracts\Database\ConnectionResolver;
use Imhotep\Contracts\IContainer;
use Imhotep\Support\Traits\Macroable;
use InvalidArgumentException;

class DatabaseManager implements ConnectionResolver
{
    use Macroable {
        __call as macroCall;
    }

    protected IContainer $container;

    protected IConfigRepository $config;

    protected ConnectionFactory $factory;

    protected Closure $reconnector;

    protected array $drivers = [
        'mysql' => [
            'connector' => \Imhotep\Database\Mysql\Connector::class,
            'connection' => \Imhotep\Database\Mysql\Connection::class,
        ],
        'mariadb' => [
            'connector' => \Imhotep\Database\MariaDb\Connector::class,
            'connection' => \Imhotep\Database\MariaDb\Connection::class,
        ],
        'pgsql' => [
            'connector' => \Imhotep\Database\Postgres\Connector::class,
            'connection' => \Imhotep\Database\Postgres\Connection::class,
        ],
        'sqlite' => [
            'connector' => \Imhotep\Database\Sqlite\Connector::class,
            'connection' => \Imhotep\Database\Sqlite\Connection::class,
        ]
    ];

    protected array $extends = [];

    protected array $connections = [];

    public function __construct(IContainer $container)
    {
        $this->container = $container;

        $this->config = $container->make(IConfigRepository::class);

        $this->factory = new ConnectionFactory();

        $this->reconnector = fn(ConnectionContract $connection) => $this->reconnect($connection->getName());
    }

    public function connection(?string $name = null): ConnectionContract
    {
        $name = $name ?: $this->getDefaultConnection();

        return $this->connections[$name] ??
            $this->connections[$name] = $this->configureConnection($this->makeConnection($name));
    }

    protected function makeConnection(string $name): ConnectionContract
    {
        $config = $this->config->subsetOrFail("database.connections.{$name}",
            "Connection [{$name}] not configured");

        $config['name'] = $name;

        $driver = $config->stringOrFail("driver", "Driver invalid");

        if (isset($this->extends[$name])) {
            return call_user_func($this->extends[$name], $config, $name);
        }

        if (isset($this->extends[$driver])) {
            return call_user_func($this->extends[$driver], $config, $name);
        }

        if (isset($this->drivers[$driver])) {
            return $this->factory->make(
                $this->drivers[$driver]['connector'],
                $this->drivers[$driver]['connection'],
                $config);
        }

        throw new InvalidArgumentException(
            "Unsupported driver [{$driver}] for connection [{$name}]"
        );
    }

    protected function configureConnection(ConnectionContract $connection): ConnectionContract
    {
        if ($this->container->bound('events')) {
            $connection->setEventDispatcher($this->container['events']);
        }

        $connection->setReconnector($this->reconnector);

        return $connection;
    }

    public function reconnect(?string $name = null): ConnectionContract
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        $newConn = $this->makeConnection($name);

        return $this->connections[$name]
            ->setPdo($newConn->getRawPdo())
            ->setReadPdo($newConn->getRawReadPdo());
    }

    public function disconnect(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
        }
    }

    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    public function usingConnection(string $name, callable $callback): mixed
    {
        $previousName = $this->getDefaultConnection();

        $this->setDefaultConnection($name);

        try {
            return $callback();
        } finally {
            $this->setDefaultConnection($previousName);
        }
    }

    public function setReconnector(Closure $reconnector): static
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    public function getDefaultConnection(): string
    {
        return $this->config->get('database.default');
    }

    public function setDefaultConnection(string $name): static
    {
        $this->config->set("database.default", $name);

        return $this;
    }

    public function extend(string $name, callable $resolver): static
    {
        $this->extends[$name] = $resolver;

        return $this;
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->connection()->{$method}(...$parameters);
    }
}