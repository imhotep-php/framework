<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Config;

interface Repository
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get all the configuration items for the application.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the specified configuration value.
     *
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string|array $key, mixed $default = null): mixed;


    /**
     * Get the specified configuration values.
     *
     * @param array $keys
     * @return mixed
     */
    public function getMany(array $keys): mixed;

    /**
     * Set a given configuration value.
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public function set(string|array $key, mixed $value): void;

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function prepend(string $key, mixed $value): void;

    /**
     * Push a value onto an array configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function push(string $key, mixed $value): void;
}