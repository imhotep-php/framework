<?php declare(strict_types=1);

namespace Imhotep\Filesystem;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\DriverManager;
use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Filesystem\Adapters\CloudAdapter;
use Imhotep\Filesystem\Adapters\LocalAdapter;
use Imhotep\Filesystem\Drivers\FtpDriver;
use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\Filesystem\Drivers\SimpleS3Driver;

class FilesystemManager extends DriverManager
{
    public function disk(?string $name = null): IFilesystem
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] ?? $this->drivers[$name] = $this->resolve($name);
    }

    public function cloud(?string $name = null): IFilesystem
    {
        $name = $name ?: $this->getDefaultCloudDriver();

        return $this->drivers[$name] ?? $this->drivers[$name] = $this->resolve($name);
    }

    protected function resolve(string $name): IFilesystem
    {
        $diskConfig = $this->config->subsetOrFail("filesystem.disks.{$name}",
            "Disk [$name] not configured."
        );

        $driverName = $diskConfig->stringOrFail('driver',
            "Driver for disk [$name] not configured."
        );

        return $this->driver($driverName, [$diskConfig]);
    }

    protected function createLocalDriver(IConfigRepository $config): IFilesystem
    {
        return new LocalAdapter(
            new LocalDriver($config->bool('throw', true)),
            $config
        );
    }

    protected function createFtpDriver(IConfigRepository $config): IFilesystem
    {
        return new LocalAdapter(new FtpDriver($config), $config);
    }

    protected function createSimpleS3Driver(IConfigRepository $config): IFilesystem
    {
        return new CloudAdapter(
            new SimpleS3Driver($config->all()),
            $config->all()
        );
    }

    public function getDefaultDriver(): string
    {
        return $this->config->stringOrFail('filesystem.default');
    }

    public function setDefaultDriver(string $driver): static
    {
        $this->config['filesystem.default'] = $driver;

        return $this;
    }

    public function getDefaultCloudDriver(): string
    {
        return $this->config->stringOrFail('filesystem.cloud');
    }

    public function setDefaultCloudDriver(string $driver): static
    {
        $this->config['filesystem.cloud'] = $driver;

        return $this;
    }
}
