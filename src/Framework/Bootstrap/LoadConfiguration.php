<?php

declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use Exception;
use Imhotep\Config\Repository;
use Imhotep\Contracts\Config\Repository as RepositoryContract;
use Imhotep\Framework\Application;

class LoadConfiguration
{
    /**
     * Create bootstrap for configuration
     *
     * @param Application $app
     */
    public function __construct(
        protected Application $app
    ){}

    /**
     * Bootstrap given application
     *
     * @return void
     * @throws Exception
     */
    public function bootstrap(): void
    {
        $this->app->instance('config', $config = new Repository([]));

        if ($this->app->configIsCached()) {
            $items = require $this->app->configCachePath();

            $config->set($items);
        }
        else {
            $this->loadConfigFiles($config);
        }

        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load configurations items from all files
     *
     * @param RepositoryContract $repository
     * @return void
     * @throws Exception
     */
    protected function loadConfigFiles(RepositoryContract $repository): void
    {
        $files = $this->getConfigFiles();

        if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach($files as $key => $file){
            $value = require $file;
            if(is_array($value)){
                $repository->set($key, $value);
            } else {
                throw new Exception('Configuration file "'.$key.'" is not array.');
            }
        }
    }

    /**
     * Get all configuration files for application
     *
     * @return array
     */
    protected function getConfigFiles(): array
    {
        $configPath = realpath($this->app->configPath());
        $filenames = array_diff(scandir($configPath), ['..','.']);

        $files = [];
        foreach($filenames as $filename){
            if (! str_ends_with($filename, '.php')) {
                continue;
            }

            $files[ basename($filename, '.php') ] = $configPath.DIRECTORY_SEPARATOR.$filename;
        }

        return $files;
    }
}