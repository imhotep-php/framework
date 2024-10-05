<?php

declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use Exception;
use Imhotep\Console\Input\InputArgv;
use Imhotep\Console\Output\ConsoleOutput;
use Imhotep\Framework\Application;
use Imhotep\Support\Env;

class LoadEnvironment
{
    /**
     * Create bootstrap for environment
     *
     * @param Application $app
     */
    public function __construct(
        protected Application $app
    ){}

    public function bootstrap(): void
    {
        $this->checkSpecificEnvironmentFile();

        try {
            Env::initRepository($this->app->environmentFilePath());
        }
        catch (Exception $e) {
            $this->writeErrorAndDie($e);
        }
    }

    protected function checkSpecificEnvironmentFile(): void
    {
        if ($this->app->runningInConsole() && ($input = new InputArgv())->hasRawOption('--env')) {
            if ($this->setEnvironment($input->getRawOption('--env'))) {
                return;
            }
        }

        if ($environment = Env::get('APP_ENV')) {
            $this->setEnvironment($environment);
        }
    }

    protected function setEnvironment(string $environment): bool
    {
        $filename = $this->app->environmentFile().'.'.$environment;

        if (is_file($this->app->environmentPath().DIRECTORY_SEPARATOR.$filename)) {
            $this->app->setEnvironmentFile($filename);

            return true;
        }

        return false;
    }

    protected function writeErrorAndDie(Exception $e): void
    {
        $output = (new ConsoleOutput())->getErrorOutput();

        $output->writeln($e->getMessage());

        exit(1);
    }
}