<?php

declare(strict_types=1);

namespace Imhotep\Routing;

use Imhotep\Contracts\Http\Request;
use Imhotep\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $parameters = $request->route()->parameters();

        $url = $parameters['__destination'] ?? '/';
        $status = $parameters['__status'] ?? 302;

        return new RedirectResponse($url, $status);
    }
}