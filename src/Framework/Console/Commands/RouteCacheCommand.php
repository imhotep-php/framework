<?php

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Facades\Route;

class RouteCacheCommand extends Command
{
    public static string $defaultName = 'route:cache';

    public static string $defaultDescription = 'Create a route cache file for faster route registration';

    public function handle(): void
    {
        $routes = Route::getRoutes();

        $cacheData = "<?php\n\n";

        foreach ($routes as $route) {
            $c = $route->cache();

            $c['methods'] = implode(',', array_map(fn($v) => "'$v'", $c['methods']));
            $c['action'] = "['type' => '{$c['action']['type']}', 'uses' => '{$c['action']['uses']}']";

            $cacheData.= "Route::match([{$c['methods']}], '{$c['uri']}', {$c['action']})->cache([\n";
            $cacheData.= "  'domain' => '{$c['domain']}',\n";
            $cacheData.= "  'regexUri' => '{$c['regexUri']}',\n";
            $cacheData.= "  'regexDomain' => '{$c['regexDomain']}',\n";
            $cacheData.= "  'params' => json_decode('".json_encode($c['params'])."'),\n";
            $cacheData.= "  'bindingFields' => json_decode('".json_encode($c['bindingFields'])."'),\n";
            $cacheData.= "  'wheres' => json_decode('".json_encode($c['wheres'])."'),\n";
            $cacheData.= "  'name' => '{$c['name']}',\n";
            $cacheData.= "]);\n\n";
        }

        file_put_contents($this->app->basePath('storage/framework/route.php'), $cacheData);

        $this->components()->success('Cached all routes is success');
    }
}