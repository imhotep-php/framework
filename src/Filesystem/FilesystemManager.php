<?php

declare(strict_types=1);

namespace Imhotep\Filesystem;

use Imhotep\Container\Container;
use Imhotep\Contracts\Filesystem\FilesystemException;
use Imhotep\Filesystem\Adapters\CloudAdapter;
use Imhotep\Filesystem\Adapters\LocalAdapter;
use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\Filesystem\Drivers\SimpleS3Driver;

class FilesystemManager
{
    protected array $disks = [];

    protected array $drivers = [
        'local' => [
            'adapter' => LocalAdapter::class,
            'driver' => LocalDriver::class
        ],
        'simple_s3' => [
            'adapter' => CloudAdapter::class,
            'driver' => SimpleS3Driver::class
        ]
    ];

    public function __construct(protected Container $app) {}

    public function disk(string $name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->disks[$name] ?? $this->disks[$name] = $this->resolve($name);
    }

    public function cloud(string $name = null)
    {
        $name = $name ?: $this->getDefaultCloudDriver();

        return $this->disks[$name] ?? $this->disks[$name] = $this->resolve($name);
    }

    protected function resolve(string $name)
    {
        $config = $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new FilesystemException("Driver for disk [$name] not configured.");
        }

        if (empty($this->drivers[$config['driver']])) {
            throw new FilesystemException(sprintf("Driver [%s] not supported.", $config['driver']));
        }

        $adapterClass = $this->drivers[$config['driver']]['adapter'];
        $driverClass = $this->drivers[$config['driver']]['driver'];

        return $this->disks[ $name ] = $this->app->make($adapterClass, [
            'driver' => $this->app->make($driverClass, ['config' => $config]),
            'config' => $config
        ]);
    }

    protected function getDefaultDriver(): string
    {
        return (string)$this->app['config']['filesystem.default'] ?? 'local';
    }

    protected function getDefaultCloudDriver(): string
    {
        return (string)$this->app['config']['filesystem.cloud'] ?? 's3';
    }

    protected function getConfig(string $name): array
    {
        return (array)$this->app['config']['filesystem.disks.'.$name] ?? [];
    }

    /*
    public function extend(string $driverName, \Closure|string $driver)
    {


    */


    public function __call(string $method, array $parameters): mixed
    {
        $disk = $this->disk();

        if (method_exists($disk, $method)) {
            return $this->disk()->$method(...$parameters);
        }

        throw new FilesystemException("Method [$method] not found.");
    }
}