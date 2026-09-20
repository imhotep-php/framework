<?php declare(strict_types=1);

namespace Imhotep\Contracts\Database;

interface ConnectionResolver
{
    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     * @return IConnection
     */
    public function connection(?string $name = null): IConnection;

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string;

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return ConnectionResolver
     */
    public function setDefaultConnection(string $name): static;
}