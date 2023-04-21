<?php

declare(strict_types=1);

namespace Imhotep\Framework\Http\Middleware;

class ShareErrorsFromSessionToView
{
    public function handle($request, \Closure $next)
    {
        /*
        $this->view->share(
            'errors', $request->session()->get('errors') ?: new ViewErrorBag
        );
        */

        return $next($request);
    }
}