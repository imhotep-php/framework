<?php declare(strict_types=1);

namespace Imhotep\Framework;

use Closure;
use Imhotep\Console\Output\ConsoleOutput;
use Imhotep\Container\Container;
use Imhotep\Contracts\Console\Input;
use Imhotep\Contracts\Console\Kernel as ConsoleKernel;
use Imhotep\Contracts\Http\Kernel as HttpKernel;
use Imhotep\Contracts\Http\Request;
use Imhotep\Events\EventServiceProvider;
use Imhotep\Filesystem\Filesystem;
use Imhotep\Framework\Providers\ProviderAdapter;
use Imhotep\Http\Exceptions\HttpException;
use Imhotep\Http\Exceptions\NotFoundHttpException;
use Imhotep\Log\LogServiceProvider;
use Imhotep\Routing\RoutingServiceProvider;

class Application extends Container
{
    /**
     * The Imhotep framework version
     *
     * @var string
     */
    const VERSION = '1.1.0';

    protected ProviderAdapter $providers;

    /**
     * Create a new application instance.
     *
     * @param string|null $basePath
     * @return void
     */
    public function __construct(string $basePath)
    {
        $this->setBasePath($basePath);

        $this->providers = new ProviderAdapter($this);

        $this->registerBaseBindings();
        $this->registerBaseAliases();
        $this->registerBaseServiceProviders();
    }

    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);


        $this->singleton(PackageManager::class, function () {
            $cachePath = env('APP_PACKAGES_CACHE', $this->storagePath('bootstrap/packages.php'));

            return new PackageManager(new Filesystem(), $this->basePath, $cachePath);
        });
    }

    protected function registerBaseAliases(): void
    {
        $this->alias('app', [self::class, Container::class, \Psr\Container\ContainerInterface::class]);
        $this->alias('request', [\Imhotep\Contracts\Http\Request::class, \Imhotep\Http\Request::class]);
    }

    protected function registerBaseServiceProviders(): void
    {
        $this->providers->register(new LogServiceProvider($this));
        $this->providers->register(new EventServiceProvider($this));
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


    //--------------------------------------------------------------------------
    // Path configurations
    //--------------------------------------------------------------------------

    protected string $basePath = '';

    protected string $publicDir = 'public';

    protected ?string $environmentPath = null;

    protected ?string $environmentFile = null;

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

    public function configCachePath(): string
    {
        return $this->storagePath('/bootstrap/config.cache.php');
    }

    public function configIsCached(): bool
    {
        return file_exists($this->configCachePath());
    }

    public function environmentPath(): string
    {
        return $this->environmentPath ?: $this->basePath;
    }

    public function setEnvironmentPath(string $path): static
    {
        $this->environmentPath = $path;

        return $this;
    }

    public function environmentFile(): string
    {
        return $this->environmentFile ?: '.env';
    }

    public function setEnvironmentFile(string $file): static
    {
        $this->environmentFile = $file;

        return $this;
    }

    public function environmentFilePath(): string
    {
        return $this->environmentPath().DIRECTORY_SEPARATOR.$this->environmentFile();
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


    //--------------------------------------------------------------------------
    // Bootstrap
    //--------------------------------------------------------------------------

    protected bool $bootstraped = false;

    protected bool $booted = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    public function bootstrapWith(array $bootstrappers): void
    {
        if($this->bootstraped) return;

        $this->bootstraped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap();
        }

        $this->providers->register('*');

        $this->boot();
    }

    public function boot(): void
    {
        if($this->booted) return;

        $this->callAppCallbacks($this->bootingCallbacks);

        $this->providers->boot('*');

        $this->booted = true;

        $this->callAppCallbacks($this->bootedCallbacks);
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }


    //--------------------------------------------------------------------------
    // Running
    //--------------------------------------------------------------------------

    protected ?bool $isRunningInConsole = null;

    public function runningInConsole(): bool
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = env('APP_RUNNING_IN_CONSOLE') ?? (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        }

        return $this->isRunningInConsole;
    }

    public function handleRequest(Request $request): void
    {
        $kernel = $this->make(HttpKernel::class);

        $response = $kernel->handle($request)->send();

        $kernel->terminate($request, $response);
    }

    public function handleCommand(Input $input): int
    {
        $kernel = $this->make(ConsoleKernel::class);

        $status = $kernel->handle($input, new ConsoleOutput);

        $kernel->terminate($input, $status);

        return $status;
    }


    //--------------------------------------------------------------------------
    // Terminate
    //--------------------------------------------------------------------------

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


    //--------------------------------------------------------------------------
    // Callbacks
    //--------------------------------------------------------------------------

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

    /**
     * Call any callbacks for the application.
     *
     * @param string $target
     * @param Closure $callback
     * @return void
     */
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