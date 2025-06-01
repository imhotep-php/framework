<?php declare(strict_types=1);

namespace Imhotep\Framework\Exceptions;

use Closure;
use Exception;
use Imhotep\Console\Output\ConsoleOutput;
use Imhotep\Container\Container;
use Imhotep\Contracts\Auth\AuthenticationException;
use Imhotep\Contracts\Auth\AuthorizationException;
use Imhotep\Contracts\Console\Output;
use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Http\HttpException as HttpExceptionContract;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Responsable;
use Imhotep\Contracts\Http\Response as ResponseContract;
use Imhotep\Framework\Exceptions\Decorators\AuthorizationExceptionDecorator;
use Imhotep\Http\Exceptions\HttpException;
use Imhotep\Http\JsonResponse;
use Imhotep\Http\Response;
use Imhotep\Routing\Router;
use Imhotep\Support\Arr;
use Imhotep\Support\Reflector;
use Imhotep\Validation\ValidationException;
use Imhotep\View\View;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class Handler implements ExceptionHandler
{
    protected Container $container;

    /**
     * A list of the exception types that are not reported.
     */
    protected array $dontReport = [];

    protected array $internalDontReport = [
        HttpException::class,
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
    ];

    /**
     * The callbacks that should be used during reporting.
     */
    protected array $reportCallbacks = [];

    protected array $levels = [];

    protected array $renderCallbacks = [];

    protected array $dontFlash = ['password', 'password_confirm', 'password_current'];

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->register();
    }

    public function register(): void
    {

    }

    public function context(): array
    {
        return [];
    }

    public function reportable(string $class, callable $callback): ReportableHandler
    {
        if (! $callback instanceof Closure) {
            $callback = $callback(...);
        }

        $callback = new ReportableHandler($callback);

        $this->reportCallbacks[$class] = $callback;

        return $callback;
    }

    public function report(Throwable $e): void
    {
        if (! $this->shouldReport($e)) {
            return;
        }

        if (Reflector::isCallable($callable = [$e, 'report'])) {
            if ($this->container->call($callable) === false) {
                return;
            }
        }

        if (isset($this->reportCallbacks[get_class($e)])) {
            if ($this->reportCallbacks[get_class($e)]($e) === false) {
                return;
            }
        }

        try {
            $logger = $this->container->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $e;
        }

        $level = LogLevel::ERROR;

        foreach ($this->levels as $class => $classLevel) {
            if (get_class($e) === $class) {
                $level = $classLevel;
                break;
            }
        }

        $context = array_merge(
            (method_exists($e, 'context')) ? $e->context() : [],
            $this->context(),
            ['exception' => $e]
        );

        method_exists($logger, $level)
            ? $logger->{$level}($e->getMessage(), $context)
            : $logger->log($level, $e->getMessage(), $context);
    }

    public function shouldReport(Throwable $e): bool
    {
        $dontReport = array_merge($this->internalDontReport, $this->dontReport);

        foreach ($dontReport as $type) {
            if ($e instanceof $type) return false;
        }

        return true;
    }

    public function renderable(string $class, callable $callback): void
    {
        $this->renderCallbacks[$class] = $callback;
    }


    protected array $decorators = [
        AuthorizationException::class => AuthorizationExceptionDecorator::class,
    ];

    public function applyDecorator(Throwable $e, Request $request): Throwable
    {
        $name = get_class($e);

        if (isset($this->decorators[$name])) {
            return ($this->decorators[$name])::decorate($e, $request);
        }

        return $e;
    }


    public function render(Throwable $e, Request $request): ResponseContract
    {
        $e = $this->applyDecorator($e, $request);

        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return Router::toResponse($response, $request);
        }

        if ($e instanceof Responsable) {
            return $e->toResponse($request);
        }

        if ($e instanceof ValidationException) {
            return $this->renderValidationExceptionResponse($e, $request);
        }

        if ($e instanceof AuthenticationException) {
            return $this->renderUnauthenticationResponse($e, $request);
        }

        return $this->renderExceptionResponse($e, $request);

        /*
        echo "<pre>";var_dump($e);
        die();

        // Render for debug
        $response = $this->container['view']->make('errors::exception', $this->getExceptionDataForRender($e));

        if ($response instanceof View) {
            $response = new Response($response->render(), 200, ['Content-Type' => 'text/html']);
        }

        return $response;
        */
    }

    protected function renderExceptionResponse(Throwable $e, Request $request): ResponseContract
    {
        if (! $this->isHttpException($e) && config('app.debug') === false) {
            $e = new HttpException(500, 'Server error');
        }

        return $request->expectsJson() ? $this->prepareJsonResponse($e) : $this->prepareResponse($e, $request);
    }

    protected function prepareJsonResponse(Throwable $e): ResponseContract
    {
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

        if ($this->isHttpException($e)) {
            return new JsonResponse([
                'message' => $e->getMessage()
            ], $e->getStatusCode(), $e->getHeaders(), $jsonOptions);
        }

        return new JsonResponse(
            $this->convertExceptionToArray($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : [],
            $jsonOptions
        );
    }

    protected function convertExceptionToArray(Throwable $e): array
    {
        if (! config('app.debug')) {
            return ['message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error'];
        }

        $trace = $e->getTrace();
        array_walk($trace, function(&$value) {
            unset($value['args']);
        });

        return [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $trace,
        ];
    }

    protected function prepareResponse(Throwable $e, Request $request): ResponseContract
    {
        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }

        return $this->renderException($e, $request);
    }

    protected function renderHttpException(HttpExceptionContract $e): ResponseContract
    {
        $this->registerErrorViewPath();

        if ($view = $this->getHttpExceptionView($e)) {
            $response = $this->container['view']->make($view, [
                'exception' => $e
            ]);

            return new Response($response->render(), $e->getStatusCode(), $e->getHeaders());
        }

        return new Response($e->getMessage(), $e->getStatusCode(), $e->getHeaders());
    }

    protected function renderException(Throwable $e, Request $request): ResponseContract
    {
        $whoops = new \Whoops\Run;
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        return new Response($whoops->handleException($e), 500, []);
    }

    protected function renderValidationExceptionResponse(ValidationException $e, Request $request): ResponseContract
    {
        if ($e->response) return $e->response;

        if ($request->expectsJson()) {
            return new JsonResponse(['messages' => $e->getMessage(), 'errors' => $e->errors()->messages()], $e->status);
        }

        return redirect($e->redirectTo ?? url()->previous())
            ->withInput(Arr::except($request->input(), $this->dontFlash))
            ->withErrors($e->errors());
    }

    protected function renderUnauthenticationResponse(AuthenticationException $e, Request $request): ResponseContract
    {
        if ($request->expectsJson()) {
            return new JsonResponse($e->getMessage(), 401);
        }

        return redirect()->guest($e->redirectTo() ?? route('login'));
    }

    protected function registerErrorViewPath(): void
    {
        $this->container['view.finder']->addNamespace('errors', [
            __DIR__.'/views',
            resource_path('/views/errors')
        ]);
    }

    protected function getExceptionDataForRender(Throwable $e): array
    {
        /*
        if ($prev = $e->getPrevious()) {
            $trace = [[
                'file' => trim(str_replace($this->container->basePath(), "", $prev->getFile()), '/'),
                'line' => $prev->getLine(),
                'function' => '',
                'class' => '',
                'code' => $this->getFileContent($prev->getFile(), $prev->getLine()),
            ]];
        }
        */

        foreach ($e->getTrace() as $item) {
            if (empty($item['file']) && isset($item['args'])) {
                $item['file'] = $item['args'][2] ?? 'none';
                $item['line'] = $item['args'][3] ?? '0';
            }
            else {
                foreach ($item['args'] as $key => $val) {
                    if (is_object($item['args'][$key])) {
                        if ($val instanceof \ReflectionParameter) {
                            $item['args'][$key] = $val->getName();
                        }
                        else {
                            $item['args'][$key] = get_class($val);
                        }
                    }
                }
            }

            $trace[] = [
                'file' => trim(str_replace($this->container->basePath(), "", $item['file']), '/'),
                'line' => $item['line'],
                'function' => $item['function'],
                'class' => $item['class'] ?? '',
                'code' => $this->getFileContent($item['file'], $item['line']),
                'args' => $item['args'],
            ];
        }

        return [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => json_encode($trace),
            'php' => phpversion(),
            'imhotep' => $this->container->version(),
            'version' => [
                'imhotep' => $this->container->version(),
                'php' => phpversion(),
            ],
            'container' => [
                'aliases' => $this->container->getAliases(),
                'bindings' => $this->container->getBindings(),
                'instances' => $this->container->getInstances(),
                'contextual' => $this->container->getContextual()
            ]
        ];
    }

    protected function getFileContent($fileName, $fileLine)
    {
        if (! is_file($fileName)) {
            return [];
        }

        $code = [];

        $file = file($fileName);
        for ($line = $fileLine-30; $line<$fileLine+30; $line++) {
            if ($line < 0) $line = 0;
            if ($line >= count($file)) break;

            $code[] = [
                'line' => $line + 1,
                'code' => $file[$line],
            ];
        }

        return $code;
    }

    protected function getHttpExceptionView(HttpExceptionContract $e): ?string
    {
        $view = 'errors::'.$e->getStatusCode();

        if ($this->container['view']->exists($view)) {
            return $view;
        }

        $view = substr($view, 0, -2).'xx';

        if ($this->container['view']->exists($view)) {
            return $view;
        }

        return null;
    }

    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpExceptionContract;
    }


    public function renderForConsole(Throwable $e, Output $output = null): void
    {
        if (is_null($output)) {
            $output = new ConsoleOutput();
        }

        //(new Error($output))->render($e->getMessage());

        $output->newLine();

        $output->writeln("<error> ". $e->getMessage() ." in ".$e->getFile()." on line ". $e->getLine() ." </error>");

        echo $this->jTraceEx($e);

        $output->newLine();
    }

    protected function jTraceEx($e, $seen = null): string
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result = array();
        if (!$seen) $seen = array();
        $trace = $e->getTrace();
        $prev = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file = $e->getFile();
        $line = $e->getLine();
        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace) + 1);
                break;
            }
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line === null ? '' : $line);
            if (is_array($seen))
                $seen[] = "$file:$line";
            if (!count($trace))
                break;
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = join("\n", $result);
        if ($prev)
            $result .= "\n" . $this->jTraceEx($prev, $seen);

        return $result;
    }
}