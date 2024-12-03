<?php

namespace Imhotep\Contracts\Http;

interface Kernel
{
  /**
   * Bootstrap the application
   *
   * @return void
   */
  public function bootstrap(): void;

  /**
   * Handle an incoming request/command
   *
   * @param Request $request
   * @return Response
   */
  public function handle(Request $request): Response;

  /**
   * Perform any final actions for the request lifecycle.
   *
   * @param Request $request
   * @param Response $response
   * @return void
   */
  public function terminate(Request $request, Response $response): void;
}