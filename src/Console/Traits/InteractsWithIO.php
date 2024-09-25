<?php

declare(strict_types=1);

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