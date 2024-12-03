<?php declare(strict_types=1);

namespace Imhotep\Routing\Console;

use Imhotep\Console\Command\Command;
use Imhotep\Facades\Route;

class RouteListCommand extends Command
{
    public static string $defaultName = 'route:list';

    public static string $defaultDescription = 'List all registered routes';

    public function handle(): int
    {
        $routes = Route::getRoutes();

        if (empty($routes)) {
            $this->components()->info('No routes found');
            return 1;
        }

        $this->output->newLine();

        $lengths = [
            'method' => 0,
            'domain' => 0,
            'uri' => 0,
            'action' => 0,
            'name' => 0,
            'middleware' => 0,
        ];

        $routes = array_map(function ($route) use(&$lengths) {
            $action = $route->action();
            if ($action instanceof \Closure) {
                $action = 'closure';
            }
            elseif (is_array($action)) {
                $action = implode('@', $action);
            }
            $action = str_replace('App\\Http\\Controllers\\', '', $action);

            $method = $route->methods();
            $method = (count($method) == 7) ? 'ANY' : implode("|", $method);

            $middleware = implode(',', array_filter(array_map(function ($value) {
                if ($value instanceof \Closure) {
                    return '';
                }
                return basename($value);
            }, $route->getMiddleware())));

            $result = [
                'method' => $method,
                'domain' => $route->domain(),
                'uri' => $route->uri(),
                'action' => $action,
                'name' => $route->name() ?? '',
                'middleware' => $middleware,
            ];

            foreach ($result as $key => $val) {
                if (! isset($lengths[$key])) continue;

                $length = strlen((string)$val);
                if ($lengths[$key] < $length) $lengths[$key] = $length;
            }

            return $result;
        }, $routes);

        $lengths = array_map(function ($value) {
            return $value + 4;
        }, $lengths);

        $this->writeCaption($lengths);

        foreach ($routes as $route) {
            $this->writeMethod($route['method'], $lengths['method']);
            $this->writeUri($route['uri'], $lengths['uri']);
            $this->writeAction($route['action'], $lengths['action']);
            $this->writeName($route['name'], $lengths['name']);
            $this->writeMiddleware($route['middleware'], $lengths['middleware']);
            $this->output->newLine();
        }

        $this->output->newLine();

        return 0;
    }

    protected function writeCaption($lengths)
    {
        $this->output->write("<fg=default;options=bold>".str_pad('Method', $lengths['method'])."</>");
        $this->output->write("<fg=default;options=bold>".str_pad('Path', $lengths['uri'])."</>");
        $this->output->write("<fg=default;options=bold>".str_pad('Action', $lengths['action'])."</>");
        $this->output->write("<fg=default;options=bold>".str_pad('Name', $lengths['name'])."</>");
        $this->output->write("<fg=default;options=bold>".str_pad('Middleware', $lengths['middleware'])."</>");
        $this->output->newLine();
        $this->output->newLine();
    }

    protected function writeMethod($method, $length): void
    {
        $color =  match ($method) {
            'GET|HEAD' => 'green',
            'POST', 'PUT', 'PATCH' => 'yellow',
            'DELETE' => 'red',
            default => 'white'
        };

        $method = str_pad($method, $length);

        $this->output->write("<fg=".$color.">{$method}</>");
    }

    protected function writeUri($uri, $length)
    {
        $uri = str_pad($uri, $length);

        $uri = preg_replace("/({.*?})/", "<b>$1</b>", $uri);

        $this->output->write("<fg=default>{$uri}</>");
    }

    protected function writeAction($action, $length)
    {
        $action = str_pad($action, $length);

        $this->output->write("<fg=cyan>{$action}</>");
    }

    protected function writeName($name, $length)
    {
        $name = str_pad($name, $length);

        $this->output->write("<fg=gray>{$name}</>");
    }

    protected function writeMiddleware($middleware, $length)
    {
        $middleware = str_pad($middleware, $length);

        $this->output->write("<fg=gray>{$middleware}</>");
    }
}