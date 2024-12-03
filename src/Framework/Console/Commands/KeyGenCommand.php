<?php declare(strict_types=1);

namespace Imhotep\Framework\Console\Commands;

use Imhotep\Console\Command\Command;
use Imhotep\Console\Input\InputOption;
use Imhotep\Encryption\Encrypter;
use Imhotep\Filesystem\Filesystem;

class KeyGenCommand extends Command
{
    public static string $defaultName = 'key:gen';

    public static string $defaultDescription = 'Generate new the application key';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $key = Encrypter::genKeyBase64();

        if ($this->input->hasOption('show')) {
            $this->output->writeln($key);

            return 1;
        }

        if (! $this->saveToEnvFile($key)) {
            return 1;
        }

        config('app.key', $key);

        $this->components()->info('New application key set successfully.');

        return 0;
    }

    protected function saveToEnvFile(string $key): bool
    {
        $filepath = $this->app->environmentFilePath();

        if (! file_exists($filepath)) {
            $this->components()->error('Unable to set application key. Environment file ['.$this->app->environmentFile().'] not found.');

            return false;
        }

        $regex = sprintf('/APP_KEY=%s/m', preg_quote(config('app.key'), '/'));

        $replaced = preg_replace($regex, "APP_KEY={$key}", $input = file_get_contents($filepath));

        if ($replaced === $input || is_null($replaced)) {
            $this->components()->error('Unable to set application key. No APP_KEY variable was found in the environment file.');

            return false;
        }

        file_put_contents($filepath, $replaced);

        return true;
    }

    public function getOptions(): array
    {
        return [
            InputOption::builder('show', 's')->description('Show generated application key')->build(),
        ];
    }
}