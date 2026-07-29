<?php declare(strict_types=1);

namespace Imhotep\Routing;

use Imhotep\Http\Response;
use Imhotep\View\Factory as ViewFactory;

class ViewController extends Controller
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    public function __invoke(string $view, array $data, int $status, array $headers)
    {
        return new Response($this->view->make($view, $data)->render(), $status, $headers);
    }
}