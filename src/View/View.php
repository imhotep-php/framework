<?php

declare(strict_types=1);

namespace Imhotep\View;

use Imhotep\View\Engines\Engine;

class View
{
    protected Factory $factory;

    protected Engine $engine;

    protected string $name;

    protected string $path;

    protected array $data;

    public function __construct(Factory $factory, Engine $engine, string $name, string $path, array $data)
    {
        $this->factory = $factory;
        $this->engine = $engine;
        $this->name = $name;
        $this->path = $path;
        $this->data = $data;
    }

    public function render(): string
    {
        $result = $this->engine->get($this->path, $this->data);

        return $result;
    }

    public function compiled()
    {

    }

    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        }
        else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->name;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toHtml(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}