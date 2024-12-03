<?php declare(strict_types=1);

namespace Imhotep\Routing;

use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Http\Request;
use Throwable;

class Pipeline extends \Imhotep\Support\Pipeline
{
    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param mixed $carry
     * @return mixed
     */
    protected function handleCarry(mixed $carry): mixed
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param mixed $request
     * @param Throwable $e
     * @return mixed
     *
     * @throws Throwable
     */
    protected function handleException(mixed $request, Throwable $e): mixed
    {
        if (! $this->container->bound(ExceptionHandler::class) || ! $request instanceof Request) {
            throw $e;
        }

        $handler = $this->container->make(ExceptionHandler::class);

        $handler->report($e);

        $response = $handler->render($e, $request);

        if (is_object($response) && method_exists($response, 'withException')) {
            $response->withException($e);
        }

        return $response;
    }
}