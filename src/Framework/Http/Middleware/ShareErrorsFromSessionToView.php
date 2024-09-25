<?php

declare(strict_types=1);

namespace Imhotep\Framework\Http\Middleware;

use Imhotep\Support\MessageBag;
use Imhotep\View\Factory as ViewFactory;

class ShareErrorsFromSessionToView
{
    public function __construct(protected ViewFactory $view)
    {
    }

    public function handle($request, \Closure $next)
    {
        $errors = $request->session()->get('errors');

        $this->view->share(
            'errors', new MessageBag(is_array($errors) ? $errors : []),
        );

        return $next($request);
    }
}