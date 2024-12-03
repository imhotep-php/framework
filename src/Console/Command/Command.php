<?php declare(strict_types=1);

namespace Imhotep\Console\Command;

use Exception;
use Imhotep\Console\Application as ConsoleApplication;
use Imhotep\Console\Input\ArrayInput;
use Imhotep\Console\Output\NullOutput;
use Imhotep\Console\Traits\InteractsWithIO;
use Imhotep\Console\Utils\SignatureParser;
use Imhotep\Container\Container;
use Imhotep\Contracts\Console\Command as CommandContract;
use Imhotep\Contracts\Console\Output;
use Imhotep\Framework\Application;
use Throwable;

abstract class Command implements CommandContract
{
    use InteractsWithIO;

    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    protected ?Container $container = null;

    protected ?ConsoleApplication $console = null;

    protected ?Application $app = null;

    protected string $name = '';

    public static string $defaultName = '';

    protected string $description = '';

    public static string $defaultDescription = '';

    protected string $signature = '';

    protected static array $parsedSignature = [];

    protected array $arguments = [];

    public function __construct(string $name = null)
    {
        $this->name = $name ?: static::getDefaultName();

        $this->description = static::getDefaultDescription();
    }

    public function getParsedSignature(): ?array
    {
        $current = static::class;

        if ($this instanceof ClosureCommand) {
            $current = static::class.'|'.$this->name;
        }

        if (isset(static::$parsedSignature[$current])) {
            return static::$parsedSignature[$current];
        }

        return static::$parsedSignature[$current] = SignatureParser::parse($this->signature);
    }

    public static function getDefaultName(): string
    {
        return static::$defaultName;
    }

    public static function getDefaultDescription(): string
    {
        return static::$defaultDescription;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function handle(): int
    {
        echo "Handle command [{$this->name}]";

        return 0;
    }

    public function getArguments(): array
    {
        $parsed = $this->getParsedSignature();

        return $parsed ? $parsed['arguments'] : [];
    }

    public function hasArguments(): bool
    {
        $options = $this->getArguments();

        return ! empty($options);
    }

    public function getOptions(): array
    {
        $parsed = $this->getParsedSignature();

        return $parsed ? $parsed['options'] : [];
    }

    public function hasOptions(): bool
    {
        $options = $this->getOptions();

        return ! empty($options);
    }

    public function fail(Throwable|string $exception = null): void
    {
        if (is_null($exception)) {
            $exception = 'Command failed.';
        }

        if (is_string($exception)) {
            $exception = new Exception($exception);
        }

        throw $exception;
    }

    public function call(string $command, array $parameters = []): int
    {
        return $this->console->call($command, $parameters);
    }

    public function callSilent(string $command, array $parameters = []): int
    {
        return $this->console->call($command, $parameters, new NullOutput());
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