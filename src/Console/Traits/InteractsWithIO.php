<?php declare(strict_types=1);

namespace Imhotep\Console\Traits;

use Imhotep\Contracts\Console\ConsoleException;
use Imhotep\Contracts\Console\Input as InputContract;
use Imhotep\Contracts\Console\Output as OutputContract;

trait InteractsWithIO
{
    protected InputContract $input;

    protected OutputContract $output;

    public function setInput(InputContract $input): static
    {
        $this->input = $input;

        return $this;
    }

    public function setOutput(OutputContract $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function arguments(): array
    {
        return $this->input->getArguments();
    }

    public function argument(string $name, mixed $default = null): mixed
    {
        return $this->input->getArgument($name, $default);
    }

    public function hasArgument(string $name): bool
    {
        return $this->input->hasArgument($name);
    }

    public function options(string $name, mixed $default = null): mixed
    {
        return $this->input->getOptions();
    }

    public function option(string $name, mixed $default = null): mixed
    {
        return $this->input->getOption($name, $default);
    }

    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }

    public function info(string $line): static
    {
        return $this->line($line, 'info');
    }

    public function success(string $line): static
    {
        return $this->line($line, 'success');
    }

    public function warn(string $line): static
    {
        return $this->line($line, 'warn');
    }

    public function error(string $line): static
    {
        return $this->line($line, 'error');
    }

    public function line(string $line = '', string $style = null): static
    {
        if (empty($line)) {
            $this->output->newLine();
        }
        else {
            $this->output->writeLn(is_null($style) ? $line : "<{$style}>$line</{$style}>");
        }

        return $this;
    }

    public function newLine(int $count = 1): static
    {
        $this->output->newLine($count);

        return $this;
    }

    public function blockInfo(string $message): static
    {
        $this->components()->info($message);

        return $this;
    }

    public function blockWarn(string $message): static
    {
        $this->components()->warn($message);

        return $this;
    }

    public function blockError(string $message): static
    {
        $this->components()->error($message);

        return $this;
    }

    public function blockSuccess(string $message): static
    {
        $this->components()->success($message);

        return $this;
    }

    public function alert(string $message, string $color = 'yellow'): static
    {
        $this->components()->alert($message, $color);

        return $this;
    }

    public function bulletList(array $elements, string $marker = '•'): static
    {
        $this->components()->bulletList($elements, $marker);

        return $this;
    }

    public function twoColumnDetail(string $label, string $value): static
    {
        $this->components()->twoColumnDetail($label, $value);

        return $this;
    }

    public function task(string $label, \Closure $task): static
    {
        $this->components()->task($label, $task);

        return $this;
    }

    public function components(): object
    {
        return new class($this->output)
        {
            public function __construct(protected OutputContract $output) { }

            public function __call($method, $parameters)
            {
                $component = 'Imhotep\Console\Formatter\Components\\'.ucfirst($method);

                if (! class_exists($component)) {
                    throw new ConsoleException(sprintf(
                        'Console component [%s] not found.', $method
                    ));
                }

                return (new $component($this->output))->render(...$parameters);
            }
        };
    }
}