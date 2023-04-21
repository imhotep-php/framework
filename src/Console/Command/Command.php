<?php

declare(strict_types=1);

namespace Imhotep\Console\Command;

use Imhotep\Console\Application as ConsoleApplication;
use Imhotep\Console\Traits\InteractsWithIO;
use Imhotep\Container\Container;
use Imhotep\Contracts\Console\Command as CommandContract;
use Imhotep\Framework\Application;

abstract class Command implements CommandContract
{
    use InteractsWithIO;

    protected ?Container $container = null;

    protected ?ConsoleApplication $console = null;

    protected ?Application $app = null;

    protected string $name = '';

    public static string $defaultName = '';

    protected string $description = '';

    public static string $defaultDescription = '';

    protected array $arguments = [];

    public function __construct()
    {
        $this->name = static::$defaultName;
        $this->description = static::$defaultDescription;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        static::$defaultName = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        static::$defaultDescription = $description;

        return $this;
    }

    public function handle(): void
    {
        echo "Handle command [{$this->name}]";
    }

    public function getOptions(): array
    {
        return [];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function setApplication(Application $app): static
    {
        $this->app = $app;

        return $this;
    }

    public function setConsole(ConsoleApplication $app): static
    {
        $this->console = $app;

        return $this;
    }
}