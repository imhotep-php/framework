<?php

declare(strict_types=1);

namespace Imhotep\Contracts;

use Closure;

interface Pipeline
{
  /**
   * Set the traveler object being sent on the pipeline.
   *
   * @param  mixed  $traveler
   * @return $this
   */
  public function send(mixed $passable);

  /**
   * Set the stops of the pipeline.
   *
   * @param  dynamic|array  $stops
   * @return $this
   */
  public function through(mixed $stops);

  /**
   * Set the method to call on the stops.
   *
   * @param  string  $method
   * @return $this
   */
  public function via(string $method);

  /**
   * Run the pipeline with a final destination callback.
   *
   * @param  \Closure  $destination
   * @return mixed
   */
  public function then(Closure $destination);
}