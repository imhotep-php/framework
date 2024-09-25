<?php

declare(strict_types=1);

namespace Imhotep\Framework;

use Closure;
use Imhotep\Container\Container;
use Imhotep\Filesystem\Filesystem;
use Imhotep\Framework\Providers\ProviderAdapter;
use Imhotep\Http\Exceptions\HttpException;
use Imhotep\Http\Exceptions\NotFoundHttpException;
use Imhotep\Routing\RoutingServiceProvider;

/**
 *
 */
class Application extends Container
{
    //use extAppTerminate, extAppBootstrap, extAppServices;

    /**
     * The Imhotep framework version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    public int $id;

    protected ProviderAdapter $providers;

    /**
     * Create a new application instance.
     *
     * @param string|null $basePath
     * @return void
     */
    public function __construct(string $basePath = null)
    {
        $this->id = rand(0, 1000);

        if (! is_null($basePath)) {
            $this->setBasePath($basePath);
        }

        $this->providers = new ProviderAdapter($this);

        $this->registerBaseBindings();
        $this->registerBaseAliases();
        $this->registerBaseServiceProviders();
    }

    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);


        $this->singleton(PackageManager::class, function () {
            $cachePath = env('APP_PACKAGES_CACHE', $this->storagePath('bootstrap/packages.php'));

            return new PackageManager(new Filesystem(), $this->basePath, $cachePath);
        });
    }

    protected function registerBaseAliases()
    {
        $this->alias('app', [self::class, Container::class, \Psr\Container\ContainerInterface::class]);
        $this->alias('cache', [\Imhotep\Cache\CacheManager::class]);
        //$this->alias('router', [\Imhotep\Contracts\Routing\Router::class, \Imhotep\Routing\Router::class]);
        $this->alias('request', [\Imhotep\Contracts\Http\Request::class, \Imhotep\Http\Request::class]);
        //$this->alias('redirect', [\Imhotep\Routing\Redirector::class]);
        //$this->alias('events', [\Imhotep\Events\Events::class]);
    }

    protected function registerBaseServiceProviders()
    {
        $this->providers->register(new RoutingServiceProvider($this));
    }

    public function version(): string
    {
        return static::VERSION;
    }

    public function locale(): string
    {
        return config('app.locale');
    }


    protected ?bool $isRunningInConsole = null;

    public function runningInConsole(): bool
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = env('APP_RUNNING_IN_CONSOLE') ?? (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    /*
    |--------------------------------------------------------------------------
    | Path configurations
    |--------------------------------------------------------------------------
    */

    protected string $basePath = '';

    protected string $publicDir = 'public';

    /**
     * Set the base path for the application.
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

    public function basePath(string $path = null): string
    {
        return $this->basePath . (empty($path) ? '' : '/'.ltrim($path, '/') );
    }

    public function configPath(string $path = null): string
    {
        return $this->basePath . '/config' . (empty($path) ? '' : '/'.ltrim($path, '/'));
    }

    public function databasePath(string $path = null): string
    {
        return $this->basePath . '/database' . (empty($path) ? '' : '/'.ltrim($path, '/'));
    }

    public function resourcePath(string $path = null): string
    {
        return $this->basePath . '/resources' . (empty($path) ? '' : '/'.ltrim($path, '/'));
    }

    public function storagePath(string $path = null): string
    {
        return $this->basePath .  '/storage' . (empty($path) ? '' : '/'.ltrim($path, '/'));
    }

    public function setPublicDir(string $publicDir = '')
    {
        $this->publicDir = $publicDir;
    }

    public function publicPath(string $path = null): string
    {
        return $this->basePath . '/'. $this->publicDir . (empty($path) ? '' : '/'.ltrim($path, '/'));
    }

    public function path(string $path = null): string
    {
        return $this->basePath . '/app' . (empty($path) ? '' : '/'.ltrim($path, '/'));
    }

    protected ?string $namespace = null;

    public function getNamespace(): string
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);
        if (isset($composer['autoload']['psr-4'])) {
            foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                foreach ((array) $path as $pathChoice) {
                    if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
                        return $this->namespace = $namespace;
                    }
                }
            }
        };

        throw new \RuntimeException('Unable to detect application namespace.');
    }

    /*
    |--------------------------------------------------------------------------
    | Bootstrap
    |--------------------------------------------------------------------------
    */

    protected bool $isBootstraped = false;

    protected bool $isBooted = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];


    public function bootstrapWith(array $bootstrappers): void
    {
        if($this->isBootstraped){
            return;
        }

        $this->isBootstraped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap();
        }

        $this->providers->register('*');

        $this->boot();
    }

    public function boot(): void
    {
        if($this->isBooted()){
            return;
        }

        $this->callAppCallbacks($this->bootingCallbacks);

        $this->providers->boot('*');

        $this->booted = true;

        $this->callAppCallbacks($this->bootedCallbacks);
    }

    public function isBooted(): bool
    {
        return $this->isBooted;
    }

    /*
    |--------------------------------------------------------------------------
    | Terminate
    |--------------------------------------------------------------------------
    */

    protected array $terminateCallbacks = [];

    public function terminate(): void
    {
        $this->callAppCallbacks($this->terminateCallbacks);
    }

    public function abort(int $code, string $message = '', array $headers = []): void
    {
        if ($code == 404) {
            throw new NotFoundHttpException();
        }

        throw new HttpException($code, $message, $headers);
    }


    /*
    |--------------------------------------------------------------------------
    | Common methods
    |--------------------------------------------------------------------------
    */

    public function booting(Closure $callback): void
    {
        $this->addAppCallback('booting', $callback);
    }

    public function booted(Closure $callback): void
    {
        $this->addAppCallback('booted', $callback);
    }

    public function terminating(Closure $callback): void
    {
        $this->addAppCallback('terminating', $callback);
    }

    protected function addAppCallback(string $target, Closure $callback): void
    {
        if($target == 'booting'){
            $this->bootingCallbacks[] = $callback;
        }

        if($target == 'booted'){
            $this->bootedCallbacks[] = $callback;
        }

        if($target == 'terminate' || $target == 'terminating'){
            $this->terminateCallbacks[] = $callback;
        }
    }


    /**
     * Call any callbacks for the application.
     *
     * @param  callable[]  $callbacks
     * @return void
     */
    protected function callAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }


    public function getDebug(): array
    {
        return [
            'version' => $this->version(),
            'path' => $this->basePath,
            'providers' => $this->providers->getDebug(),
            'container' => parent::getDebug(),
        ];
    }
}