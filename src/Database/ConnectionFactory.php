<?php declare(strict_types=1);

namespace Imhotep\Database;

class ConnectionFactory
{
    protected array $driver;

    protected array $config;

    public function make(array $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;

        return $this->createSingle();
    }

    protected function createSingle()
    {
        return $this->createConnection($this->resolvePdo());
    }

    protected function resolvePdo()
    {
        return $this->createConnector()->connect($this->config);
    }

    protected function createConnector()
    {
        return new $this->driver['connector'];
    }

    protected function createConnection($pdo)
    {
        return new $this->driver['connection']($pdo, $this->config);
    }
}