<?php declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use Exception;
use Imhotep\Config\Repository as ConfigRepository;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Framework\Application;
use Throwable;

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
        $this->app->alias('config', [ConfigRepository::class, IConfigRepository::class]);
        $this->app->instance('config', $config = new ConfigRepository([]));

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
     * @param IConfigRepository $repository
     * @return void
     * @throws Exception
     */
    protected function loadConfigFiles(IConfigRepository $repository): void
    {
        $files = $this->getConfigFiles();

        if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach($files as $key => $file){
            try {
                $value = require $file;

                if(! is_array($value)){
                    throw new Exception('Configuration file "'.$key.'" is not array.');
                }

                $repository->set($key, $value);
            }
            catch (Throwable $e) {
                throw new Exception('Configuration file "'.$key.'" load failed: '.$e->getMessage());
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