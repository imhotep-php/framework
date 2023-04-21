<?php

namespace Imhotep\Contracts\Http;

interface Kernel
{
  /**
   * Bootstrap the application
   *
   * @return void
   */
  public function bootstrap();

  /**
   * Handle an incoming request/command
   *
   * @param Request $request
   * @return Response
   */
  public function handle(Request $request);

  /**
   * Perform any final actions for the request lifecycle.
   *
   * @param Request $request
   * @param Response $response
   * @return void
   */
  public function terminate($request, $response);

  /**
   * Get application instance.
   *
   * @return \Imhotep\Framework\Application
   */
  public function getApplication();
}