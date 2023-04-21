<?php

declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use Imhotep\Console\Output\ConsoleOutput;
use Imhotep\Dotenv\Dotenv;
use Imhotep\Dotenv\DotenvException;
use Imhotep\Framework\Application;

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
        try {
            $dotenv = new Dotenv($this->app->basePath());
            $this->app->instance('dotenv', $dotenv);
            $this->app->alias('dotenv', Dotenv::class);
        } catch (DotenvException $e) {
            $this->writeErrorAndDie($e);
        }
    }

    protected function writeErrorAndDie(DotenvException $e): void
    {
        $output = (new ConsoleOutput())->getErrorOutput();

        $output->writeln($e->getMessage());

        exit(1);
    }
}