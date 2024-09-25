<?php declare(strict_types=1);

namespace Imhotep\Contracts\Database;

interface ConnectionResolver
{
    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     * @return Connection
     */
    public function connection(string $name = null): Connection;

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string;

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection(string $name): void;
}