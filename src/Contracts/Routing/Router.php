<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Routing;

use Imhotep\Contracts\Http\Request;

interface Router
{
  public function dispatch(Request $request);
}