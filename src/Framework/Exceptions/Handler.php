<?php

declare(strict_types=1);

namespace Imhotep\Framework\Exceptions;

use Closure;
use Exception;
use Imhotep\Container\Container;
use Imhotep\Contracts\Console\Output;
use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Http\HttpException as HttpExceptionContract;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Responsable;
use Imhotep\Contracts\Http\Response as ResponseContract;
use Imhotep\Http\Exceptions\HttpException;
use Imhotep\Http\Response;
use Imhotep\Routing\Router;
use Imhotep\Support\Reflector;
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
        HttpException::class
    ];

    /**
     * The callbacks that should be used during reporting.
     */
    protected array $reportCallbacks = [];

    protected array $levels = [];

    protected array $renderCallbacks = [];

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->container['view.finder']->addNamespace(__DIR__.'/views', 'errors');

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

    public function render(Throwable $e, Request $request): ResponseContract
    {
        /*if (method_exists($e, 'render')) {
            if ($response = $e->render($request)) {
                return Router::toResponse($response, $request);
            }
        }

        if ($e instanceof Responsable) {
            return $e->toResponse($request);
        }*/

        if (! $this->isHttpException($e) && config('app.debug') === false) {
            $e = new HttpException(500, $e->getMessage());
        }

        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }

        // Render for debug
        $response = $this->container['view']->make('errors::exception', $this->getExceptionDataForRender($e));

        if ($response instanceof View) {
            $response = new Response($response->render(), 200, ['Content-Type' => 'text/html']);
        }

        return $response;
    }

    public function renderHttpException(HttpExceptionContract $e): ResponseContract
    {
        if ($view = $this->getHttpExceptionView($e)) {
            $response = $this->container['view']->make($view, [
                'exception' => $e
            ]);

            return new Response($response->render(), 200, ['Content-Type' => 'text/html']);
        }

        return $this->renderHttpException(new HttpException(500, $e->getMessage()));
    }

    protected function getExceptionDataForRender(Throwable $e): array
    {
        $trace = [];

        foreach ($e->getTrace() as $item) {
            $code = [];
            $file = file($item['file']);
            for ($line = $item['line']-30; $line<$item['line']+30; $line++) {
                if ($line < 0) $line = 0;
                if ($line >= count($file)) break;

                $code[] = [
                    'line' => $line,
                    'code' => $file[$line],
                ];
            }

            $trace[] = [
                'file' => trim(str_replace($this->container->basePath(), "", $item['file']), '/'),
                'line' => $item['line'],
                'function' => $item['function'],
                'class' => $item['class'] ?? '',
                'code' => $code,
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
        ];
    }

    protected function getFileContent($file, $line)
    {

    }

    public function renderForConsole(Throwable $e, Output $output): void
    {
        //(new Error($output))->render($e->getMessage());

        $output->newLine();

        $output->writeln("<error> ". $e->getMessage() ." </error>");

        $output->newLine();
    }


    // Methods for HttpException
    public function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpExceptionContract;
    }

    public function getHttpExceptionView(HttpExceptionContract $e): ?string
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
}