<?php

namespace Imhotep\Contracts\Debug;

use Imhotep\Contracts\Console\Output;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Response;
use Throwable;

interface ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * @param  Throwable  $e
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $e): void;

    /**
     * Determine if the exception should be reported.
     *
     * @param  Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e): bool;

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Throwable  $e
     * @param  Request  $request
     * @return Response
     *
     * @throws Throwable
     */
    public function render(Throwable $e, Request $request): Response;

    /**
     * Render an exception to the console.
     *
     * @param  Throwable  $e
     * @param  Output  $output
     * @return void
     *
     * @internal This method is not meant to be used or overwritten outside the framework.
     */
    public function renderForConsole(Throwable $e, Output $output): void;
}
