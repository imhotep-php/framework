<?php declare(strict_types=1);

namespace Imhotep\Database;

use Exception;
use Imhotep\Contracts\Database\DatabaseException;
use Imhotep\Database\Traits\DetectsErrors;
use PDO;

abstract class Connector
{
    use DetectsErrors;

    protected array $config;

    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function connect(array $config): PDO
    {
        $this->config = $config;

        return $this->configure(
            $this->create($this->getDsn(), $this->getOptions())
        );
    }

    protected function create(string $dsn, array $options = []): PDO
    {
        [$username, $password] = [
            $this->config['username'] ?? null,
            $this->config['password'] ?? null,
        ];

        $connect = fn() => new PDO($dsn, $username, $password, $options);

        try {
            return $connect();
        }
        catch (Exception $e) {
            if (! $this->causedByLostConnection($e)) {
                throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
            }

            return $connect();
        }
    }

    abstract protected function configure(PDO $connection): PDO;

    abstract public function getDsn(): string;

    public function getOptions(): array
    {
        return array_replace($this->options, $this->config['options'] ?? []);
    }
}