<?php declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use Imhotep\Framework\Application;
use Imhotep\Http\Request;

class SetRequestForConsole
{
    /**
     * Create bootstrap for facades
     *
     * @param Application $app
     */
    public function __construct(
        protected Application $app
    ){}

    public function bootstrap(): void
    {
        $uri = config('app.url', 'http://localhost');

        $components = parse_url($uri);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
            ]);
        }

        $this->app->instance('request', Request::create(
            $uri, 'GET', [], [], [], $server
        ));
    }
}