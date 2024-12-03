<?php declare(strict_types=1);

namespace Imhotep\Console\Command;

use Closure;
use ReflectionFunction;

class ClosureCommand extends Command
{
    protected Closure $callback;

    public function __construct(string $signature, Closure $callback, string $name = null)
    {
        parent::__construct($name);

        $this->signature = $signature;

        $this->callback = $callback;
    }

    public function handle(): int
    {
        $inputs = array_merge($this->input->getArguments(), $this->input->getOptions());

        $parameters = [];

        foreach ((new ReflectionFunction($this->callback))->getParameters() as $parameter) {
            if (isset($inputs[$parameter->getName()])) {
                $parameters[$parameter->getName()] = $inputs[$parameter->getName()];
            }
        }

        $ret = $this->app->call($this->callback->bindTo($this, $this), $parameters);

        return is_numeric($ret) ? $ret : 0;
    }

    public function describe(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}