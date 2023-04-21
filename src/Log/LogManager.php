<?php

declare(strict_types=1);

namespace Imhotep\Log;

use Imhotep\Container\Container;
use Imhotep\Log\Formatter\TelegramFormatter;
use Imhotep\Log\Handlers\FileHandler;
use Imhotep\Log\Handlers\RotatingFileHandler;
use Imhotep\Log\Handlers\TelegramHandler;
use Psr\Log\LoggerInterface;

class LogManager implements LoggerInterface
{
    protected string $dateFormat = 'Y-m-d H:i:s';

    protected array $drivers = [];

    protected array $sharedContext = [];

    public function __construct(protected Container $app) {}

    public function channels(): array
    {
        return $this->drivers;
    }

    public function getChannels(): array
    {
        return $this->drivers;
    }

    public function channel(string $channel = null): LoggerInterface
    {
        return $this->driver($channel);
    }

    public function driver(string $driver = null): LoggerInterface
    {
        $driver = empty($driver) ? $this->getDefaultDriver() : $driver;

        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        $config = $this->getChannelConfig($driver);

        $config['name'] = $config['name'] ?? $this->app['config']['app.env'] ?? 'production';

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->drivers[$driver] = $this->{$driverMethod}($config);
        }

        throw new \Exception("Logging driver [{$driver}] not found.");
    }

    public function forgetChannel(string $name = null): static
    {
        $driver = empty($name) ? $this->getDefaultDriver() : $name;

        if (isset($this->drivers[$driver])) {
            unset($this->drivers[$driver]);
        }

        return $this;
    }


    protected function createStackDriver($config): LoggerInterface
    {
        $handlers = [];

        foreach ((array)$config['channels'] as $channel) {
            if ($channel === 'stack') continue;

            foreach ($this->channel($channel)->getHandlers() as $handler) {
                $handlers[] = $handler;
            }
        }

        return new Logger($config['name'], $handlers);
    }

    protected function createSingleDriver($config): LoggerInterface
    {
        return new Logger($config['name'], [
            new FileHandler($config['path'], $config['level'] ?? 'debug')
        ]);
    }

    protected function createDailyDriver($config): LoggerInterface
    {
        return new Logger($config['name'], [
            new RotatingFileHandler($config['path'], $config['days'], $config['level'] ?? 'debug')
        ]);
    }

    protected function createTelegramDriver($config): LoggerInterface
    {
        $handler = new TelegramHandler($config['token'], $config['chat_id'], $config['level'] ?? 'debug');

        $handler->setFormatter(new TelegramFormatter());

        return new Logger($config['name'], [$handler]);
    }

    protected function getConfig(): array
    {
        $config = $this->app['config']['logging'];

        return is_array($config) ? $config : [];
    }

    protected function getDefaultDriver(): string
    {
        $config = $this->getConfig();

        if (! isset($config['default'])) {
            throw new \Exception("Default logging is not configured.");
        }

        return $config['default'];
    }

    protected function getChannelConfig(string $channel): array
    {
        $config = $this->app['config']->get('logging.channels.'.$channel);

        if (! is_array($config)) {
            throw new \Exception("Logging channel [$channel] is not configured.");
        }

        return $config;
    }


    public function shareContext(array $context = null): static|array
    {
        if (is_null($context)) {
            return $this->sharedContext;
        }

        foreach ($this->drivers as $driver) {
            $driver->withContext($context);
        }

        $this->sharedContext = array_merge($this->sharedContext, $context);

        return $this;
    }

    public function sharedContext(): array
    {
        return $this->sharedContext;
    }

    public function flushSharedContext(): static
    {
        $this->sharedContext = [];

        return $this;
    }

    public function emergency(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = array()): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function log($level, $message, array $context = array()): void
    {
        $this->driver()->log($level, $message, $context);
    }

    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}