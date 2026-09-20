<?php declare(strict_types=1);

namespace Imhotep\Database;

use Closure;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Database\IConnection as ConnectionContract;
use InvalidArgumentException;
use PDOException;

class ConnectionFactory
{
    protected string $connectorClass;

    protected string $connectionClass;

    protected IConfigRepository $config;

    public function make(string $connectorClass, string $connectionClass, IConfigRepository $config): ConnectionContract
    {
        $this->connectorClass = $connectorClass;
        $this->connectionClass = $connectionClass;
        $this->config = $config;

        return $this->config->has('read')
            ? $this->createReadWriteConnection()
            : $this->createSingleConnection();
    }

    protected function createSingleConnection(): ConnectionContract
    {
        $config = $this->getConfig('write');

        return $this->createConnection(
            $this->createPdoResolver($config),
            $config
        );
    }

    protected function createReadWriteConnection(): ConnectionContract
    {
        $config = $this->getConfig('read');

        return $this->createSingleConnection()
            ->setReadPdo($this->createPdoResolver($config));
    }

    protected function createConnector(): Connector
    {
        if (!class_exists($this->connectorClass)) {
            throw new InvalidArgumentException(
                "Connector class '{$this->connectorClass}' not found"
            );
        }

        return new $this->connectorClass;
    }

    protected function createConnection($pdo, array $config): ConnectionContract
    {
        if (!class_exists($this->connectionClass)) {
            throw new InvalidArgumentException(
                "Connection class '{$this->connectionClass}' not found"
            );
        }

        return new $this->connectionClass($pdo, $config);
    }

    protected function createPdoResolver(array $config): Closure
    {
        return $this->hasMultipleHosts($config)
            ? $this->createMultiHostResolver($config)
            : $this->createSimpleResolver($config);
    }

    protected function hasMultipleHosts(array $config): bool
    {
        return isset($config['host']) && is_array($config['host']);
    }

    protected function createMultiHostResolver(array $config): Closure
    {
        return function () use ($config) {
            $hosts = $config['host'];

            if (empty($hosts)) {
                throw new InvalidArgumentException('Database host cannot be empty');
            }

            shuffle($hosts);

            $lastException = null;

            foreach ($hosts as $host) {
                $config['host'] = $host;

                try {
                    return $this->createConnector()->connect($config);
                } catch (PDOException $e) {
                    $lastException = $e;
                    continue;
                }
            }

            throw $lastException ?? new PDOException(
                "Could not connect to any database host"
            );
        };
    }

    protected function createSimpleResolver(array $config): Closure
    {
        return fn() => $this->createConnector()->connect($config);
    }

    protected function getConfig(string $type): array
    {
        $base = $this->config->except(['read', 'write']);
        $specific = $this->config->array($type, []);


        return array_merge($base, $specific);
    }
}