<?php declare(strict_types=1);

namespace Imhotep\Database\Commands\Migrations;

use Imhotep\Console\Command\Command;
use Imhotep\Console\Input\InputOption;
use Imhotep\Database\Migrations\Migrator;

class BaseCommand extends Command
{
    public function __construct(
        protected Migrator $migrate
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->migrate->setInput($this->input);
        $this->migrate->setOutput($this->output);
        $this->migrate->setConnection($this->input->getOption('database'));

        return 0;
    }

    protected function getPaths(): array
    {
        $paths = [];

        if ($this->input->hasOption('path')) {
            $paths += (array)$this->input->getOption('path');
        }

        if ($this->input->getOption('realpath')) {
            foreach ($paths as $key => $path) {
                $paths[$key] = $this->container->basePath($path);
            }
        }

        $paths = array_merge($paths, $this->migrate->paths());

        return array_unique($paths);
    }

    public function getOptions(): array
    {
        return [
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_ARRAY, 'The path(s) to the migrations files to use'),
            new InputOption('realpath', null, InputOption::VALUE_OPTIONAL, 'Indicate any provided migration file paths are pre-resolved absolute paths'),
        ];
    }
}